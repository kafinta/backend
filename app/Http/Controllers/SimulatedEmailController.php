<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SimulatedEmailController extends Controller
{
    /**
     * Display the simulated emails interface
     */
    public function index()
    {
        // Create directory if it doesn't exist
        $emailsDir = storage_path('simulated-emails');
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

        return view('emails.simulated.index', [
            'initialEmails' => json_encode([
                'success' => true,
                'data' => [
                    'emails' => $emails
                ]
            ])
        ]);
    }

    /**
     * Display a specific simulated email
     */
    public function show($filename)
    {
        $filepath = storage_path('simulated-emails/' . $filename);

        if (!File::exists($filepath)) {
            abort(404, 'Email not found');
        }

        $content = File::get($filepath);

        return response($content)->header('Content-Type', 'text/html');
    }

    /**
     * Delete a specific simulated email
     */
    public function destroy($filename)
    {
        $filepath = storage_path('simulated-emails/' . $filename);

        if (!File::exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found'
            ], 404);
        }

        File::delete($filepath);

        return response()->json([
            'success' => true,
            'message' => 'Email deleted successfully'
        ]);
    }

    /**
     * Clear all simulated emails
     */
    public function clearAll()
    {
        $emailsDir = storage_path('simulated-emails');
        
        if (File::exists($emailsDir)) {
            File::deleteDirectory($emailsDir);
            File::makeDirectory($emailsDir, 0755, true);
        }

        return response()->json([
            'success' => true,
            'message' => 'All emails cleared successfully'
        ]);
    }
}
