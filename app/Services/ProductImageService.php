<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductImageService
{
    protected $tempDisk = 'public';
    protected $storageDisk = 'public';
    protected $tempPath = 'temp-product-images';
    protected $finalPath = 'product-images';

    public function handleImageStep(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $paths = [];
        
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $paths[] = $path;
            }
        }
        
        return $paths;
    }

    public function processProductImages(array $tempImages, Product $product)
    {
        foreach ($tempImages as $tempImagePath) {
            $this->moveImageToFinalLocation($tempImagePath, $product);
        }
    }

    protected function moveImageToFinalLocation(string $tempImagePath, Product $product)
    {
        $fullTempPath = Storage::disk($this->tempDisk)->path($tempImagePath);
        $extension = pathinfo($fullTempPath, PATHINFO_EXTENSION);
        
        $finalPath = Storage::disk($this->storageDisk)->putFileAs(
            "{$this->finalPath}/{$product->id}", 
            $fullTempPath,
            'image_' . time() . '_' . uniqid() . '.' . $extension
        );

        $product->images()->create(['path' => $finalPath]);
        Storage::disk($this->tempDisk)->delete($tempImagePath);
    }

    public function deleteProductImages(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk($this->storageDisk)->delete($image->path);
            $image->delete();
        }
    }

    public function validateImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, ['image/jpeg', 'image/png']);
    }
}
