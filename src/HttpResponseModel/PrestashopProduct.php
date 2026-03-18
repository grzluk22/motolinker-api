<?php

declare(strict_types=1);

namespace App\HttpResponseModel;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

class PrestashopProduct
{
    public function __construct(
        #[OA\Property(description: 'ID produktu z Prestashop')]
        public ?int $id = null,
        
        #[SerializedName('id_manufacturer')]
        public ?int $idManufacturer = null,
        
        #[SerializedName('id_supplier')]
        public ?int $idSupplier = null,
        
        #[SerializedName('id_category_default')]
        public ?int $idCategoryDefault = null,
        
        public ?string $new = null,
        
        #[SerializedName('cache_default_attribute')]
        public ?int $cacheDefaultAttribute = null,
        
        #[SerializedName('id_default_image')]
        public ?int $idDefaultImage = null,
        
        #[SerializedName('id_default_combination')]
        public ?int $idDefaultCombination = null,
        
        #[SerializedName('id_tax_rules_group')]
        public ?int $idTaxRulesGroup = null,
        
        #[SerializedName('position_in_category')]
        public ?int $positionInCategory = null,
        
        #[SerializedName('manufacturer_name')]
        public ?string $manufacturerName = null,
        
        public ?int $quantity = null,
        public ?string $type = null,
        
        #[SerializedName('id_shop_default')]
        public ?int $idShopDefault = null,
        
        #[OA\Property(description: 'Nr referencyjny / kod produktu')]
        public ?string $reference = null,
        
        #[SerializedName('supplier_reference')]
        public ?string $supplierReference = null,
        
        public ?string $location = null,
        public ?string $width = null,
        public ?string $height = null,
        public ?string $depth = null,
        public ?string $weight = null,
        
        #[SerializedName('quantity_discount')]
        public ?string $quantityDiscount = null,
        
        public ?string $ean13 = null,
        public ?string $isbn = null,
        public ?string $upc = null,
        public ?string $mpn = null,
        
        #[SerializedName('cache_is_pack')]
        public ?string $cacheIsPack = null,
        
        #[SerializedName('cache_has_attachments')]
        public ?string $cacheHasAttachments = null,
        
        #[SerializedName('is_virtual')]
        public ?string $isVirtual = null,
        
        public ?int $state = null,
        
        #[SerializedName('additional_delivery_times')]
        public ?int $additionalDeliveryTimes = null,
        
        #[SerializedName('delivery_in_stock')]
        public ?string $deliveryInStock = null,
        
        #[SerializedName('delivery_out_stock')]
        public ?string $deliveryOutStock = null,
        
        #[SerializedName('product_type')]
        public ?string $productType = null,
        
        #[SerializedName('on_sale')]
        public ?string $onSale = null,
        
        #[SerializedName('online_only')]
        public ?string $onlineOnly = null,
        
        public ?string $ecotax = null,
        
        #[SerializedName('minimal_quantity')]
        public ?int $minimalQuantity = null,
        
        #[SerializedName('low_stock_threshold')]
        public ?int $lowStockThreshold = null,
        
        #[SerializedName('low_stock_alert')]
        public ?string $lowStockAlert = null,
        
        #[OA\Property(description: 'Cena netto')]
        public ?string $price = null,
        
        #[SerializedName('wholesale_price')]
        public ?string $wholesalePrice = null,
        
        public ?string $unity = null,
        
        #[SerializedName('unit_price')]
        public ?string $unitPrice = null,
        
        #[SerializedName('unit_price_ratio')]
        public ?string $unitPriceRatio = null,
        
        #[SerializedName('additional_shipping_cost')]
        public ?string $additionalShippingCost = null,
        
        public ?int $customizable = null,
        
        #[SerializedName('text_fields')]
        public ?int $textFields = null,
        
        #[SerializedName('uploadable_files')]
        public ?int $uploadableFiles = null,
        
        #[OA\Property(description: 'Czy produkt jest aktywny')]
        public ?string $active = null,
        
        #[SerializedName('redirect_type')]
        public ?string $redirectType = null,
        
        #[SerializedName('id_type_redirected')]
        public ?int $idTypeRedirected = null,
        
        #[SerializedName('available_for_order')]
        public ?string $availableForOrder = null,
        
        #[SerializedName('available_date')]
        public ?string $availableDate = null,
        
        #[SerializedName('show_condition')]
        public ?string $showCondition = null,
        
        public ?string $condition = null,
        
        #[SerializedName('show_price')]
        public ?string $showPrice = null,
        
        public ?string $indexed = null,
        public ?string $visibility = null,
        
        #[SerializedName('advanced_stock_management')]
        public ?string $advancedStockManagement = null,
        
        #[SerializedName('date_add')]
        public ?string $dateAdd = null,
        
        #[SerializedName('date_upd')]
        public ?string $dateUpd = null,
        
        #[SerializedName('pack_stock_type')]
        public ?int $packStockType = null,
        
        #[SerializedName('meta_description')]
        public ?string $metaDescription = null,
        
        #[SerializedName('meta_title')]
        public ?string $metaTitle = null,
        
        #[SerializedName('link_rewrite')]
        public ?string $linkRewrite = null,
        
        #[OA\Property(description: 'Nazwa produktu')]
        public ?string $name = null,
        
        #[OA\Property(description: 'Opis produktu')]
        public ?string $description = null,
        
        #[SerializedName('description_short')]
        public ?string $descriptionShort = null,
        
        #[SerializedName('available_now')]
        public ?string $availableNow = null,
        
        #[SerializedName('available_later')]
        public ?string $availableLater = null,
        
        public ?array $associations = null
    ) {
    }
}
