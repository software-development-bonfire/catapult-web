<?php

namespace App\Traits;

use App\Entities\CDISProductUomPackaging;
use Illuminate\Support\Facades\Storage;

trait ProductImagePathTrait
{
    /**
     * Get the presentation image path for a product.
     *
     * @param string $productBid
     * @param bool $fullUrl When true, returns the full URL with host via storage symbolic link.
     * @return string
     */
    public function getProductImagePath($productBid, $fullUrl = true)
    {
        $packaging = CDISProductUomPackaging::where('bid', $productBid)->first();

        if (!$packaging || !$packaging->image_path) {
            return '';
        }
        
        $path = ltrim($packaging->image_path, '/');

        if ($fullUrl) {
            // Try Storage::url first (works with storage symlink and S3)
            $url = Storage::url($path);
            
            // If Storage::url returns a relative path, convert to absolute URL
            if (strpos($url, 'http') === false && strpos($url, '://') === false) {
                $url = asset($url);
            }
            
            return $url;
        }

        return $path;
    }

    /**
     * Get the recipe URL for a product by replacing the /product/ segment with /recipe/.
     *
     * @param string $productBid
     * @param bool $fullUrl When true, returns the full URL with host via storage symbolic link.
     * @return string
     */
    public function getProductRecipeUrl($productBid, $fullUrl = true)
    {
        $imagePath = $this->getProductImagePath($productBid, false);

        if (!$imagePath) {
            return '';
        }

        $recipePath = preg_replace('#/product/#', '/recipe/', $imagePath, 1);

        if ($fullUrl) {
            // Try Storage::url first (works with storage symlink and S3)
            $url = Storage::url($recipePath);
            
            // If Storage::url returns a relative path, convert to absolute URL
            if (strpos($url, 'http') === false && strpos($url, '://') === false) {
                $url = asset($url);
            }
            
            return $url;
        }

        return $recipePath;
    }
}
