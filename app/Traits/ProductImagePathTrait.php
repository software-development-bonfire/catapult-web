<?php

namespace App\Traits;

use App\Entities\CDISProductUomPackaging;

trait ProductImagePathTrait
{
    /**
     * Get the presentation image path for a product.
     *
     * @param string $productBid
     * @param bool $fullUrl When true, returns the full URL with host via storage symbolic link.
     * @return string
     */
    public function getProductImagePath($productBid, $fullUrl = false)
    {
        $packaging = CDISProductUomPackaging::where('bid', $productBid)->first();

        if (!$packaging || !$packaging->image_path) {
            return '';
        }

        $path = $packaging->image_path;

        if ($fullUrl) {
            return rtrim(config('app.url'), '/') . '/storage' . $path;
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
    public function getProductRecipeUrl($productBid, $fullUrl = false)
    {
        $imagePath = $this->getProductImagePath($productBid);

        if (!$imagePath) {
            return '';
        }

        $recipePath = preg_replace('#/product/#', '/recipe/', $imagePath, 1);

        if ($fullUrl) {
            return rtrim(config('app.url'), '/') . '/storage' . $recipePath;
        }

        return $recipePath;
    }
}
