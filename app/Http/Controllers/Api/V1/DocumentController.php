<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\{Document,DocumentShare,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\{StoreDocumentRequest,UpdateDocumentRequest,ShareDocumentRequest};

class DocumentController extends BaseApiController
{
    /**
     * List documents
     * - Lawyers/Admins: see firm documents
     * - Clients: see shared documents
     */
    public function index()
    {
        $user = $this->currentUser();
        $documents = Document::query();
        if ($user->isClient()) {
            $documents = $documents->whereHas('shares', function ($q) use ($user) {
                $q->where('shared_with_user_id', $user->id);
            });
        } elseif ($user->isLawyer()) {
            $documents = $documents->where('owner_id', $user->id);
        } elseif ($user->isFirmAdmin()) {
            $documents = $documents->where('firm_id', $user->firm_id);
        } else {
            return ApiResponse::error('You do not have access to any documents', null, 403);
        }
        $documents = $documents->get();
        return ApiResponse::success($documents, 'Accessible documents retrieved successfully');
    }

    /**
     * Upload a new document
     */
    public function store(StoreDocumentRequest $request)
    {
        $user = $this->currentUser();
        if (!$user->isLawyer()) {
            return ApiResponse::forbidden('Only lawyers can upload documents');
        }

        $data = $request->validated();
        $docCount = Document::where('owner_id', $user->id)->count();
        if ($docCount >= $user->firm->currentSubscription->max_documents_per_user) {
            return ApiResponse::error('Document upload limit reached', null, 422);
        }
        
        $document = Document::create([
            ...$data,
            'file_path' => '',//$path,
            'owner_id' => $user->id,
            'firm_id' => $user->firm_id,
        ]);
        $path = self::upload($request);
        $document->update(['file_path' => $path]);
        return ApiResponse::success($document, 'Document uploaded successfully', 201);
    }

    /**
     * View single document
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);
        return ApiResponse::success($document,"Document fetched successfully!");
    }

    /**
     * Update a document
     * Optional: only firm admins/system admins or owner
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $request->validated();
        $document->title = $request->title ?? $document->title;
        $document->description = $request->description ?? $document->description;
        $document->save();
        return ApiResponse::success($document, 'Document updated successfully');
    }

    /**
     * Delete a document (soft delete)
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        $document->shares()->delete();
        $document->delete();
        return ApiResponse::success(null, 'Document deleted successfully');
    }

       /**
     * Share a document with a client
     * POST /documents/{id}/share
     */
    public function share(ShareDocumentRequest $request, Document $document)
    {
        $user = $this->currentUser();
        $this->authorize('share', $document);
        $validated = $request->validated();
        $targetUser = User::where('id', $validated['shared_with_user_id'])->where('firm_id', $user->firm_id)->where('role', 'CLIENT')->first();
        // Only clients in the same firm
        if (!$targetUser->isClient() || $targetUser->firm_id !== $user->firm_id) {
            return response()->json(['message' => 'Document Can only be shared with clients in your firm'], 422);
        }
        $share = DocumentShare::Create(
            [
                'document_id' => $document->id,
                'shared_with_user_id' => $targetUser->id,
                'firm_id' =>$user->firm_id
            ],
            [
                'permission' => $request->permission,
            ]
        );
        return response()->json(['message' => 'Document shared successfully','share' => $share], 201);
    }

    /**
     * Get all documents shared with the current client
     * GET /documents/shared-with-me
     */
    public function sharedWithMe(Request $request)
    {
        $user = $this->currentUser();
        if (!$user->isClient()) {
            return response()->json(['message' => 'Only clients can access shared documents'], 403);
        }
        $documents = Document::query()->whereHas('shares', function ($q) use ($user) {
            $q->where('shared_with_user_id', $user->id)->where('permission', 'VIEW');
        })->paginate(25);
        return ApiResponse::success($documents, 'Shared Documents retrieved successfully');
    }

    public function searchDocument(Request $request)
    {
        $user = $this->currentUser();
        // Base query
        $query = Document::query();
        if ($user->isLawyer()) {
            $query->where('owner_id', $user->id);
        } elseif ($user->isFirmAdmin() || $user->isFirmSystemAdmin()) {
            $query->where('firm_id', $user->firm_id);
        } elseif ($user->isClient()) {
            $query->whereHas('shares', function ($q) use ($user) {
                $q->where('shared_with_user_id', $user->id);
            });
        } else {
            return ApiResponse::forbidden('You do not have access to documents');
        }
        // Optional: search by title/description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $documents = $query->latest()->paginate(25);
        $documents->getCollection()->transform(function ($document) use ($user) {
            if ($user->can('view', $document)) {
                return $document;
            }
            return null;
        });
        $documents->setCollection($documents->getCollection()->filter());
        return ApiResponse::success($documents, 'Documents retrieved successfully');
    }

    public static function upload($request){
        $file = $request->file('file');
        $originalName = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $file->getClientOriginalName());
        $filename = $document->id . '_' . $originalName;
        // default storage disk 
        try{
            $disk = config('filesystems.default');
            $path = Storage::disk($disk)->putFileAs(
                "documents/firm_{$user->firm_id}",
                $file,
                $filename
            );
            return $path;
        }catch (\Exception $e) {
            $document->delete(); // rollback DB record
            return ApiResponse::error('File upload failed: ' . $e->getMessage(), null, 500);
        }
    }
}