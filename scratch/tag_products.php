<?php
use App\Models\Tag;
use App\Models\Product;
use App\Models\ProductionFormula;

$tag = Tag::firstOrCreate(['name' => 'soplados']);
Product::whereIn('id', ProductionFormula::pluck('product_id'))
    ->get()
    ->each(function($p) use ($tag) {
        if(!$p->tags()->where('tags.id', $tag->id)->exists()) {
            $p->tags()->attach($tag->id);
            echo "Tagged product: {$p->name}\n";
        }
    });
echo "Done tagging products.\n";
