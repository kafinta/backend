<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class SimulatedEmailController extends ImprovedController
{
    /**
     * List all simulated emails
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $emailsDir = storage_path('simulated-emails');
            
            // Create directory if it doesn't exist
            if (!File::exists($emailsDir)) {
                File::makeDirectory($emailsDir, 0755, true);
            }
            
            // Get all email files
            $files = File::files($emailsDir);
            
            $emails = [];
            foreach ($files as $file) {
                $emails[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'url' => route('simulated-emails.show', ['filename' => $file->getFilename()]),
                ];
            }
            
            // Sort by creation time (newest first)
            usort($emails, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return $this->respondWithSuccess('Simulated emails retrieved successfully', 200, [
                'emails' => $emails,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving simulated emails: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Show a specific simulated email
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function show($filename)
    {
        try {
            $filepath = storage_path('simulated-emails/' . $filename);
            
            if (!File::exists($filepath)) {
                return $this->respondWithError('Email not found', 404);
            }
            
            $content = File::get($filepath);
            
            return Response::make($content, 200, [
                'Content-Type' => 'text/html',
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving email: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete a specific simulated email
     *
     * @param string $filename
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($filename)
    {
        try {
            $filepath = storage_path('simulated-emails/' . $filename);
            
            if (!File::exists($filepath)) {
                return $this->respondWithError('Email not found', 404);
            }
            
            File::delete($filepath);
            
            return $this->respondWithSuccess('Email deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->respondWithError('Error deleting email: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete all simulated emails
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyAll()
    {
        try {
            $emailsDir = storage_path('simulated-emails');
            
            if (!File::exists($emailsDir)) {
                return $this->respondWithSuccess('No emails to delete', 200);
            }
            
            $files = File::files($emailsDir);
            
            foreach ($files as $file) {
                File::delete($file->getPathname());
            }
            
            return $this->respondWithSuccess('All emails deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->respondWithError('Error deleting emails: ' . $e->getMessage(), 500);
        }
    }
}
