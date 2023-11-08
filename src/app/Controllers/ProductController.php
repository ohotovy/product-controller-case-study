<?php

namespace App\Controllers;

use App\Traits\IsSingleton;
use App\Models\Product;
use App\Models\ProductSearch;
use DateTime;

class ProductController
{
    use IsSingleton;

    public function detail(int $productId, string $source) : string
    {
        $product = Product::find($productId,$source);

        // pozn.: zadání mluví o tom, že marketing sleduje počet dotazů na produkt, nicméně domnívám se
        // (IRL bych se samozřejmě zeptal seniora/projekťáka :)), že informace o času dotazů
        // je/může být taktéž (velmi) relevantní, proto raději ukládám záznamy o jednotlivých hledáních.

        // if the search was successful, save the record about searching for the product
        if ($product instanceof Product) {
            $searchRecord = new ProductSearch([
                'product_id' => $productId,
                'timestamp' => (new DateTime)->format('Y-m-d H:i:s')
            ]);
            $searchRecord->save();

            // pozn.: Tady je extra implementace count na ověření, že to funguje, když už jsem se vyhnul,
            // čestný pionýrský z business logic důvodů, implementaci update metody
            // $searchCount = ProductSearch::where('product_id', $productId)->count();
            // echo "Product ID {$productId} was searched for {$searchCount} times.";
            // echo "<br>";
        }

        return $product;
    }
}