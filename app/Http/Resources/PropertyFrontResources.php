<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyFrontResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'property_code' => $this->property_code,
            'title' => $this->title,
            'slug' => $this->slug,
            'tags' => $this->tags,
            'description' => $this->description,
            'land_area' => $this->land_area,
            'land_unit_id' => $this->land_unit_id,
            'property_face' => $this->whenLoaded('propertyFace', function() {
                return [
                    'id' => $this->propertyFace->id,
                    'label' => $this->propertyFace->label,
                    'slug' => $this->propertyFace->slug,
                ];
            }),
            'listing_type' => $this->whenLoaded('listingType', function() {
                return [
                    'id' => $this->listingType->id,
                    'label' => $this->listingType->label,
                    'slug' => $this->listingType->slug,
                ];
            }),
            'property_status' => $this->whenLoaded('propertyStatus', function() {
                return [
                    'id' => $this->propertyStatus->id,
                    'label' => $this->propertyStatus->label,
                    'slug' => $this->propertyStatus->slug,
                ];
            }),
            'images' => $this->whenLoaded('images', function() {
                return $this->images->map(function($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->image_url,
                        'full_url' => $image->image_url,
                        'image_type' => $image->image_type,
                        'is_featured' => $image->is_featured,
                        'sort_order' => $image->sort_order,
                    ];
                });
            }),
            'address' => $this->whenLoaded('address', function() {
                return [
                    'full_address' => $this->address->full_address,
                ];
            }),
            'property_type_id' => $this->property_type_id,
            'property_category_id' => $this->property_category_id,
            'length' => $this->length,
            'height' => $this->height,
            'measure_unit_id' => $this->measure_unit_id,
            'is_road_accessible' => $this->is_road_accessible,
            'road_type_id' => $this->road_type_id,
            'road_condition_id' => $this->road_condition_id,
            'road_width' => $this->road_width,
            'base_price' => $this->base_price,
            'advertise_price' => $this->advertise_price,
            'currency' => $this->currency,
            'is_featured' => $this->is_featured,
            'is_negotiable' => $this->is_negotiable,
            'banking_available' => $this->banking_available,
            'has_electricity' => $this->has_electricity,
            'water_source_id' => $this->water_source_id,
            'sewage_type_id' => $this->sewage_type_id,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'video_url' => $this->video_url,
           
        ];
    }
}