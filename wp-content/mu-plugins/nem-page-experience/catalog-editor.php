<?php
/** Editable layouts layered over the existing shop, search and cart templates. */
defined('ABSPATH') || exit;

function npe_catalog_types() { return ['shop'=>'Sản phẩm','cart'=>'Giỏ hàng','search'=>'Tìm kiếm']; }

function npe_catalog_schema($type) {
    if ($type === 'shop') return [
        'heading'=>['label'=>'Tiêu đề danh sách sản phẩm','default'=>'Tất cả sản phẩm'],
        'categories_title'=>['label'=>'Tiêu đề danh mục','default'=>'Danh mục'],
        'all_categories'=>['label'=>'Nhãn tất cả sản phẩm','default'=>'Tất cả sản phẩm'],
        'size_label'=>['label'=>'Nhãn bộ lọc kích cỡ','default'=>'Kích cỡ'],
        'color_label'=>['label'=>'Nhãn bộ lọc màu sắc','default'=>'Màu sắc'],
        'price_label'=>['label'=>'Nhãn bộ lọc giá','default'=>'Giá'],
        'banner_image'=>['label'=>'Ảnh banner sản phẩm','type'=>'image','default'=>0],
        'banner_url'=>['label'=>'Liên kết banner','type'=>'url','default'=>wc_get_page_permalink('shop')],
        'empty_text'=>['label'=>'Thông báo không có sản phẩm','default'=>'Không có sản phẩm phù hợp với yêu cầu'],
    ];
    if ($type === 'search') return [
        'heading'=>['label'=>'Tiêu đề kết quả tìm kiếm','default'=>'Kết quả tìm kiếm:'],
        'search_label'=>['label'=>'Nhãn ô tìm kiếm','default'=>'Tìm kiếm sản phẩm'],
        'search_button'=>['label'=>'Nút tìm kiếm','default'=>'Tìm kiếm'],
        'empty_text'=>['label'=>'Thông báo không có kết quả','default'=>'Không tìm thấy sản phẩm phù hợp. Vui lòng thử từ khóa khác.'],
    ];
    if ($type === 'cart') return [
        'heading'=>['label'=>'Tiêu đề giỏ hàng','default'=>'Giỏ hàng'],
        'product_heading'=>['label'=>'Cột sản phẩm','default'=>'Sản phẩm'],
        'price_heading'=>['label'=>'Cột giá','default'=>'Giá'],
        'quantity_heading'=>['label'=>'Cột số lượng','default'=>'Số lượng'],
        'total_heading'=>['label'=>'Cột tổng tiền','default'=>'Tổng tiền'],
        'note_label'=>['label'=>'Nhãn chú thích','default'=>'Chú thích'],
        'total_label'=>['label'=>'Nhãn tổng giỏ hàng','default'=>'Tổng tiền'],
        'update_text'=>['label'=>'Nút cập nhật giỏ','default'=>'Cập nhật'],
        'checkout_text'=>['label'=>'Nút thanh toán','default'=>'Thanh toán'],
        'empty_text'=>['label'=>'Giỏ hàng trống','default'=>'Giỏ hàng trống'],
        'continue_text'=>['label'=>'Tiếp tục mua sắm','default'=>'Tiếp tục để mua hàng'],
    ];
    return [];
}

function npe_catalog_sections($type) {
    return ['shop'=>['banner'=>'Banner sản phẩm','catalog'=>'Danh mục và danh sách sản phẩm'], 'search'=>['form'=>'Ô tìm kiếm','heading'=>'Tiêu đề kết quả','results'=>'Kết quả tìm kiếm'], 'cart'=>['heading'=>'Tiêu đề giỏ hàng','items'=>'Danh sách sản phẩm','bottom'=>'Chú thích và tổng tiền','empty'=>'Thông báo giỏ trống']][$type] ?? [];
}

function npe_visual_required_sections($type) {
    return ['checkout'=>['contact','address','payment','summary'],'shop'=>['catalog'],'cart'=>['items','bottom'],'search'=>['form','results']][$type] ?? [];
}

function npe_catalog_group_schema($type) {
    if ($type === 'product') return [
        'product_details'=>['items'=>['gallery'=>'Ảnh sản phẩm','summary'=>'Nội dung sản phẩm'],'direction'=>'row'],
        'purchase_buttons'=>['items'=>['add_to_cart'=>'Thêm vào giỏ','buy_now'=>'Mua ngay'],'direction'=>'column'],
        'product_summary'=>['items'=>['title'=>'Tên sản phẩm','brand'=>'Thương hiệu & mã','price'=>'Giá','consult'=>'Tư vấn Zalo','purchase'=>'Nút mua hàng','size_guide'=>'Hướng dẫn size','description'=>'Mô tả'],'direction'=>'column'],
    ];
    if ($type === 'checkout') return [
        'contact_fields'=>['items'=>['name'=>'Họ và tên','phone'=>'Số điện thoại','email'=>'Email'],'direction'=>'column'],
        'address_fields'=>['items'=>['street'=>'Số nhà, tên đường','district'=>'Phường/Quận','city'=>'Tỉnh/Thành phố'],'direction'=>'column'],
        'summary_elements'=>['items'=>['items'=>'Danh sách sản phẩm','totals'=>'Bảng tính tiền','legal'=>'Điều khoản mua hàng','place_order'=>'Nút đặt hàng'],'direction'=>'column'],
    ];
    if ($type === 'home') return [
        'home_features'=>['items'=>['feature_0'=>'Thiết kế 1','feature_1'=>'Thiết kế 2'],'direction'=>'row'],
        'newsletter_form'=>['items'=>['email'=>'Ô nhập email','submit'=>'Nút đăng ký'],'direction'=>'row'],
    ];
    $card = ['items'=>['image'=>'Ảnh sản phẩm','information'=>'Tên và giá sản phẩm'],'direction'=>'column'];
    if ($type === 'shop') return [
        'catalog_columns'=>['items'=>['categories'=>'Danh mục','products'=>'Danh sách sản phẩm'],'direction'=>'row'],
        'catalog_content'=>['items'=>['toolbar'=>'Tiêu đề và bộ lọc','results'=>'Lưới sản phẩm'],'direction'=>'column'],
        'product_card'=>$card,
    ];
    if ($type === 'search') return ['search_form'=>['items'=>['input'=>'Ô nhập từ khóa','button'=>'Nút tìm kiếm'],'direction'=>'row'],'product_card'=>$card];
    if ($type === 'cart') return [
        'cart_product'=>['items'=>['image'=>'Ảnh sản phẩm','details'=>'Thông tin sản phẩm'],'direction'=>'row'],
        'cart_bottom'=>['items'=>['note'=>'Chú thích','summary'=>'Tổng tiền và thanh toán'],'direction'=>'row'],
    ];
    return [];
}

function npe_catalog_clean_layouts($type, $posted) {
    $posted = is_array($posted) ? $posted : [];
    $clean = [];
    foreach (npe_catalog_group_schema($type) as $key=>$group) {
        $value = is_array($posted[$key] ?? null) ? $posted[$key] : [];
        $allowed = array_keys($group['items']);
        $order = is_array($value['order'] ?? null) ? array_filter($value['order'],'is_string') : [];
        $clean[$key] = ['order'=>array_values(array_unique(array_merge(array_intersect($order,$allowed),$allowed))), 'direction'=>in_array($value['direction'] ?? '',['row','column'],true) ? $value['direction'] : $group['direction']];
    }
    return $clean;
}

function npe_catalog_layouts($type) {
    $settings = get_option('npe_page_builder',[]);
    return npe_catalog_clean_layouts($type,$settings[$type]['layouts'] ?? []);
}

function npe_catalog_targets($type) {
    $map = ['shop'=>[
        'heading'=>'.nem-shop-toolbar-title > .nve-label','categories_title'=>'.nem-shop-sidebar > h2','all_categories'=>'.nem-shop-all-categories',
        'size_label'=>'[data-filter-key="size"] > button > .nve-label','color_label'=>'[data-filter-key="color"] > button > .nve-label','price_label'=>'[data-filter-key="price"] > button > .nve-label','empty_text'=>'.nem-shop-no-results',
    ],'search'=>['heading'=>'.nem-shop-toolbar-title > .nve-label','search_label'=>'.npe-search-form > label','search_button'=>'.npe-search-form button','empty_text'=>'.nem-search-empty > p'],
    'cart'=>['heading'=>'.entry-title','product_heading'=>'.storefront-custom-cart-product-heading','price_heading'=>'.storefront-custom-cart-price-heading','quantity_heading'=>'.storefront-custom-cart-quantity-heading','total_heading'=>'.storefront-custom-cart-total-heading','note_label'=>'.storefront-custom-cart-note > label','total_label'=>'.storefront-custom-cart-grand-total > span','update_text'=>'.storefront-custom-cart-update','checkout_text'=>'.storefront-custom-cart-checkout','empty_text'=>'.storefront-custom-cart-empty > p','continue_text'=>'.storefront-custom-cart-empty > a']];
    $targets = [];
    foreach ($map[$type] ?? [] as $field=>$selector) $targets[$field] = ['selector'=>$selector,'field'=>$field];
    $targets['commerce_names'] = ['selector'=>$type === 'cart' ? '.storefront-custom-cart-name' : '.woocommerce-loop-product__title'];
    $targets['commerce_prices'] = ['selector'=>$type === 'cart' ? '.storefront-custom-cart-price, .storefront-custom-cart-line-total, .storefront-custom-cart-grand-total strong' : 'li.product .price'];
    $targets['commerce_text'] = ['selector'=>$type === 'cart' ? '.storefront-custom-cart-product-detail' : '.nem-shop-category-tree'];
    return $targets;
}

function npe_catalog_preview_url($type) {
    if ($type === 'cart') return wc_get_cart_url();
    if ($type === 'shop') return wc_get_page_permalink('shop');
    $query = isset($_GET['query']) && is_string($_GET['query']) ? sanitize_text_field(wp_unslash($_GET['query'])) : 'đầm';
    if ($query === '') $query = 'đầm';
    return add_query_arg(['s'=>$query,'post_type'=>'product'],home_url('/'));
}

/** Read-only sample lines: no changes to the customer's cart or session. */
add_filter('do_shortcode_tag',function ($html,$tag) {
    if ($tag !== 'storefront_custom_cart' || npe_visual_preview_type() !== 'cart') return $html;
    if (!str_contains($html,'storefront-custom-cart-empty')) return $html;
    $products = wc_get_products(['status'=>'publish','limit'=>2,'orderby'=>'ID','order'=>'ASC']);
    ob_start(); ?>
    <div class="storefront-custom-cart" data-nve-sample="true"><p class="nve-sample-notice">Sản phẩm minh họa để sửa bố cục · Giỏ hàng thực tế giữ nguyên.</p>
    <form class="storefront-custom-cart-form">
    <div class="storefront-custom-cart-head"><span class="storefront-custom-cart-product-heading">Sản phẩm</span><span class="storefront-custom-cart-price-heading">Giá</span><span class="storefront-custom-cart-quantity-heading">Số lượng</span><span class="storefront-custom-cart-total-heading">Tổng tiền</span></div>
    <div class="storefront-custom-cart-items"><?php $total=0; foreach($products as $p): $total+=(float)$p->get_price(); ?>
    <div class="storefront-custom-cart-item"><div class="storefront-custom-cart-product"><a class="storefront-custom-cart-image" href="<?php echo esc_url($p->get_permalink()); ?>"><?php echo $p->get_image('woocommerce_thumbnail'); ?></a><div class="storefront-custom-cart-details"><div class="storefront-custom-cart-name"><?php echo esc_html($p->get_name()); ?></div><div class="storefront-custom-cart-product-detail">Số lượng minh họa: 1</div><div class="storefront-custom-cart-remove"><a href="#">Xóa</a></div></div></div><div class="storefront-custom-cart-price"><?php echo wp_kses_post($p->get_price_html()); ?></div><div class="storefront-custom-cart-quantity"><input type="number" value="1" aria-label="Số lượng minh họa" disabled></div><div class="storefront-custom-cart-line-total"><?php echo wc_price($p->get_price()); ?></div></div>
    <?php endforeach; ?></div><div class="storefront-custom-cart-bottom"><div class="storefront-custom-cart-note"><label for="nve-sample-note">Chú thích</label><textarea id="nve-sample-note" disabled></textarea></div><div class="storefront-custom-cart-actions"><div class="storefront-custom-cart-grand-total"><span>Tổng tiền</span><strong><?php echo wc_price($total); ?></strong></div><div class="storefront-custom-cart-buttons"><button type="button" class="storefront-custom-cart-update">Cập nhật</button><a class="storefront-custom-cart-checkout" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Thanh toán</a></div></div></div></form></div>
    <?php return ob_get_clean();
},20,2);

function npe_catalog_nodes($xpath,$class,$context=null) {
    return iterator_to_array($xpath->query('.//*[contains(concat(" ",normalize-space(@class)," ")," '.$class.' ")]',$context));
}

function npe_catalog_group($doc,$root,$key,$elements,$layout) {
    if (!$root || count(array_filter($elements)) !== count($elements)) return;
    $root->setAttribute('data-nve-group',$key);
    $root->setAttribute('data-nve-flow',$layout['direction']);
    foreach ($layout['order'] as $item) if (isset($elements[$item])) {
        $elements[$item]->setAttribute('data-nve-piece',$item);
        $root->appendChild($elements[$item]);
    }
}

/** Apply to the original rendered HTML, preserving all forms, URLs and query results. */
function npe_catalog_transform($html,$type) {
    if (!class_exists('DOMDocument') || !isset(npe_catalog_types()[$type])) return $html;
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    if (!$loaded) return $html;
    $xpath = new DOMXPath($doc);
    $main = $xpath->query('//*[@id="main"]')->item(0);
    if (!$main) return $html;
    $main->setAttribute('data-nve-root',$type);
    $layouts = npe_catalog_layouts($type);
    $first = function($class,$context=null) use($xpath){ return npe_catalog_nodes($xpath,$class,$context)[0] ?? null; };
    $settings = npe_page_settings($type);
    $sections = [];
    if ($type === 'shop') {
        $banner=$first('nem-shop-banner'); $catalog=$first('nem-shop-layout');
        if ($banner) {
            $sections['banner']=$banner;
            $image=absint(npe_setting('shop','banner_image',0));
            if ($image && ($url=wp_get_attachment_image_url($image,'full'))) { $img=$banner->getElementsByTagName('img')->item(0); if($img){$img->setAttribute('src',$url);$img->removeAttribute('srcset');$img->removeAttribute('sizes');} }
            if (array_key_exists('banner_url',$settings['fields'])) $banner->setAttribute('href',npe_setting('shop','banner_url',''));
        }
        if ($catalog) {
            $sections['catalog']=$catalog;
            $sidebar=$first('nem-shop-sidebar',$catalog); $products=$first('nem-shop-main',$catalog);
            npe_catalog_group($doc,$catalog,'catalog_columns',['categories'=>$sidebar,'products'=>$products],$layouts['catalog_columns']);
            if ($products) npe_catalog_group($doc,$products,'catalog_content',['toolbar'=>$first('nem-shop-toolbar',$products),'results'=>$first('nem-shop-results',$products)],$layouts['catalog_content']);
        }
        $heading=$first('nem-shop-toolbar-title');
        if($heading){$span=$doc->createElement('span');$span->setAttribute('class','nve-label');while($heading->firstChild)$span->appendChild($heading->firstChild);$heading->appendChild($span);}
        foreach (['size','color','price'] as $key) foreach(npe_catalog_nodes($xpath,'nem-shop-filter') as $filter) if($filter->getAttribute('data-filter-key')===$key){$button=$filter->getElementsByTagName('button')->item(0);if($button){$span=$doc->createElement('span');$span->setAttribute('class','nve-label');foreach(iterator_to_array($button->childNodes) as $node)if($node->nodeType===XML_TEXT_NODE)$span->appendChild($node);$button->insertBefore($span,$button->firstChild);}}
    } elseif ($type === 'search') {
        $form=$doc->createElement('form');$form->setAttribute('class','npe-search-form');$form->setAttribute('role','search');$form->setAttribute('action',home_url('/'));$form->setAttribute('method','get');
        $label=$doc->createElement('label','Tìm kiếm sản phẩm');$label->setAttribute('for','npe-search-text');$form->appendChild($label);
        $input=$doc->createElement('input');foreach(['type'=>'search','name'=>'s','id'=>'npe-search-text','value'=>get_search_query(false)] as $k=>$v)$input->setAttribute($k,$v);
        $submit=$doc->createElement('button','Tìm kiếm');$submit->setAttribute('type','submit');
        $controls=$doc->createElement('div');$controls->setAttribute('class','npe-search-controls');$form->appendChild($controls);
        $input_piece=$doc->createElement('div');$input_piece->setAttribute('class','npe-search-input-piece');$input_piece->appendChild($input);
        $button_piece=$doc->createElement('div');$button_piece->appendChild($submit);$controls->appendChild($input_piece);$controls->appendChild($button_piece);
        $hidden=$doc->createElement('input');$hidden->setAttribute('type','hidden');$hidden->setAttribute('name','post_type');$hidden->setAttribute('value','product');$form->appendChild($hidden);
        npe_catalog_group($doc,$controls,'search_form',['input'=>$input_piece,'button'=>$button_piece],$layouts['search_form']);
        $main->insertBefore($form,$main->firstChild);$sections['form']=$form;
        $heading=$first('nem-shop-toolbar'); $results=$first('nem-shop-results');
        if (!$heading) $heading=$first('page-header');
        if ($heading) {
            $title=$heading->getElementsByTagName('h1')->item(0);
            if($title){while($title->firstChild)$title->removeChild($title->firstChild);$title->setAttribute('class','nem-shop-toolbar-title');$label=$doc->createElement('span','Kết quả tìm kiếm:');$label->setAttribute('class','nve-label');$title->appendChild($label);$title->appendChild($doc->createTextNode(' '.get_search_query(false)));}
            $sections['heading']=$heading;
        }
        if (!$results) { $results=$doc->createElement('div');$results->setAttribute('class','nem-shop-results');foreach(iterator_to_array($main->childNodes) as $node)if($node!==$form && $node!==$heading)$results->appendChild($node);$main->appendChild($results); }
        $sections['results']=$results;
        $empty=$first('nem-search-empty',$results); if($empty){$p=$empty->getElementsByTagName('p')->item(0);if($p){while($p->firstChild)$p->removeChild($p->firstChild);$p->appendChild($doc->createTextNode(npe_setting('search','empty_text',npe_catalog_schema('search')['empty_text']['default'])));}}
    } else {
        $title=$first('entry-header',$main);if(!$title)$title=$first('entry-title',$main);
        $cart=$first('storefront-custom-cart',$main);$empty=$first('storefront-custom-cart-empty',$main);
        $root=$cart ? $cart->parentNode : ($empty ? $empty->parentNode : $main);
        if($title){$sections['heading']=$title;$root->insertBefore($title,$root->firstChild);}
        if($cart){
            $form=$first('storefront-custom-cart-form',$cart);
            if($form){
                if($title)$form->insertBefore($title,$form->firstChild);
                $head=$first('storefront-custom-cart-head',$form);$items=$first('storefront-custom-cart-items',$form);$bottom=$first('storefront-custom-cart-bottom',$form);
                $list=$doc->createElement('section');$list->setAttribute('class','npe-cart-items-section');if($head)$list->appendChild($head);if($items)$list->appendChild($items);$form->insertBefore($list,$form->firstChild);$sections['items']=$list;
                if($bottom){$sections['bottom']=$bottom;npe_catalog_group($doc,$bottom,'cart_bottom',['note'=>$first('storefront-custom-cart-note',$bottom),'summary'=>$first('storefront-custom-cart-actions',$bottom)],$layouts['cart_bottom']);}
            }
        }
        if($empty){$sections['empty']=$empty;}
        foreach(npe_catalog_nodes($xpath,'storefront-custom-cart-product',$main) as $row) npe_catalog_group($doc,$row,'cart_product',['image'=>$first('storefront-custom-cart-image',$row),'details'=>$first('storefront-custom-cart-details',$row)],$layouts['cart_product']);
    }
    if($type!=='cart') foreach(npe_catalog_nodes($xpath,'woocommerce-loop-product__link',$main) as $link) {
        $image=$first('nem-catalog-product-media',$link);if(!$image)$image=$link->getElementsByTagName('img')->item(0);
        if(!$image)continue;
        $information=$doc->createElement('div');$information->setAttribute('class','npe-catalog-information');
        foreach(iterator_to_array($link->childNodes) as $node)if($node!==$image)$information->appendChild($node);
        $link->appendChild($information);
        npe_catalog_group($doc,$link,'product_card',['image'=>$image,'information'=>$information],$layouts['product_card']);
    }
    // Explicit field identifiers avoid changing dynamic product data or search keywords.
    foreach(npe_catalog_targets($type) as $key=>$target) {
        if(empty($target['field']))continue;
        $nodes=[];
        $selector=$target['selector'];
        if(preg_match('/^\.([a-z0-9_-]+)$/i',$selector,$m))$nodes=npe_catalog_nodes($xpath,$m[1],$main);
        elseif($key==='heading')$nodes=$type==='cart'?npe_catalog_nodes($xpath,'entry-title',$main):$xpath->query('.//*[contains(@class,"nem-shop-toolbar-title")]/span[contains(@class,"nve-label")]',$main);
        elseif($type==='shop' && in_array($key,['size_label','color_label','price_label'],true))$nodes=$xpath->query('.//*[@data-filter-key="'.str_replace('_label','',$key).'"]/button/span[contains(@class,"nve-label")]',$main);
        elseif($key==='categories_title')$nodes=$xpath->query('.//aside[contains(@class,"nem-shop-sidebar")]/h2',$main);
        elseif($key==='search_label')$nodes=$form->getElementsByTagName('label');
        elseif($key==='search_button')$nodes=$form->getElementsByTagName('button');
        elseif($key==='note_label')$nodes=$xpath->query('.//*[contains(@class,"storefront-custom-cart-note")]/label',$main);
        elseif($key==='total_label')$nodes=$xpath->query('.//*[contains(@class,"storefront-custom-cart-grand-total")]/span',$main);
        elseif(in_array($key,['empty_text','continue_text'],true) && $type==='cart')$nodes=$xpath->query('.//*[contains(@class,"storefront-custom-cart-empty")]/'.($key==='empty_text'?'p':'a'),$main);
        foreach($nodes as $node){$node->setAttribute('data-nve-field',$key);if(array_key_exists($key,$settings['fields'])){while($node->firstChild)$node->removeChild($node->firstChild);$node->appendChild($doc->createTextNode($settings['fields'][$key]));}}
    }
    foreach(npe_section_order($type,(bool)npe_visual_preview_type()) as $key) if(isset($sections[$key])) {
        $node=$sections[$key];$node->setAttribute('data-npe-section',$key);
        // Preserve the cart form boundary and its hidden nonce fields.
        $parent=$node->parentNode; $parent->appendChild($node);
    }
    if(!npe_visual_preview_type()) foreach($settings['hidden'] as $key)if(isset($sections[$key]) && !in_array($key,npe_visual_required_sections($type),true))$sections[$key]->parentNode->removeChild($sections[$key]);
    if(npe_visual_preview_type()) foreach(iterator_to_array($xpath->query('.//input[contains(@name,"nonce") or @name="storefront_custom_cart_submit"]',$main)) as $node)$node->parentNode->removeChild($node);
    foreach(iterator_to_array($doc->childNodes) as $node)if($node->nodeType===XML_PI_NODE)$doc->removeChild($node);
    return $doc->saveHTML();
}

add_filter('template_include',function($template){
    $type=npe_visual_front_type();
    if(!isset(npe_catalog_types()[$type]))return $template;
    $GLOBALS['npe_catalog_original_template']=$template;
    return __DIR__.'/catalog-shell.php';
},1000);

add_action('wp_enqueue_scripts',function(){
    $front = npe_visual_front_type();
    if(!isset(npe_catalog_types()[$front]) && !in_array($front, ['product', 'checkout', 'home'], true)) return;
    wp_enqueue_style('npe-catalog-layout',content_url('/mu-plugins/nem-page-experience/catalog-layout.css'),[],filemtime(__DIR__.'/catalog-layout.css'));
},1100);

add_action('template_redirect',function(){
    if(get_option('npe_search_layout_page') && is_page((int)get_option('npe_search_layout_page'))){wp_safe_redirect(npe_catalog_preview_url('search'));exit;}
},1);
