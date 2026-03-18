<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        if ($user->isLawyer() || $user->isFirmAdmin() || $user->isFirmSystemAdmin()) {
            $documents = Document::where('firm_id', $user->firm_id)->paginate(25);
        } elseif ($user->isClient()) {
            $documents = $user->sharedDocuments()->paginate(25);
        } else {
            return ApiResponse::forbidden();
        }
        return ApiResponse::success($documents, 'Documents retrieved successfully');
    }

    /**
     * Upload a new document
     */
    public function store(Request $request)
    {
        $user = $this->currentUser();

        if (!$user->isLawyer()) {
            return ApiResponse::forbidden('Only lawyers can upload documents');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,docx,jpg,png|max:10240', // 10MB
        ]);

        // Check subscription limit
        $docCount = Document::where('owner_id', $user->id)->count();
        if ($docCount >= $user->firm->subscription->max_documents_per_user) {
            return ApiResponse::error('Document upload limit reached', null, 422);
        }
        //$path = $request->file('file')->store('documents');
        $file = $request->file('file');
        $originalName = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $file->getClientOriginalName());

        $document = Document::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => '',//$path,
            'owner_id' => $user->id,
            'firm_id' => $user->firm_id,
        ]);

        $filename = $document->id . '_' . $originalName;
        $path = $file->storeAs('documents', $filename);
        $document->update(['file_path' => $path]);
        return ApiResponse::success($document, 'Document uploaded successfully', 201);
    }

    /**
     * View single document
     */
    public function show(Document $document)
    {
        $user = $this->currentUser();

        // Lawyer/firms access their firm's documents
        if (($user->isLawyer() || $user->isFirmAdmin() || $user->isFirmSystemAdmin()) 
            && $user->firm_id === $document->firm_id) {
            return ApiResponse::success($document);
        }

        // Clients: must be shared
        if ($user->isClient() && $document->sharedWithUsers()->where('users.id', $user->id)->exists()) {
            return ApiResponse::success($document);
        }

        return ApiResponse::forbidden('You do not have access to this document');
    }

    /**
     * Update a document
     * Optional: only firm admins/system admins or owner
     */
    public function update(Request $request, Document $document)
    {
        $user = $this->currentUser();

        if (!($user->isPlatformAdmin() || $user->isFirmSystemAdmin() || $user->isFirmAdmin() || ($user->isLawyer() && $document->owner_id == $user->id))) {
            return ApiResponse::forbidden();
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'file' => 'sometimes|file|mimes:pdf,docx,jpg,png|max:10240',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
            $document->file_path = $request->file('file')->store('documents');
        }

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
        $user = $this->currentUser();

        if (!($user->isPlatformAdmin() || $user->isFirmSystemAdmin() || $user->isFirmAdmin() || ($user->isLawyer() && $document->owner_id == $user->id))) {
            return ApiResponse::forbidden();
        }

        $document->delete();

        return ApiResponse::success(null, 'Document deleted successfully');
    }
}