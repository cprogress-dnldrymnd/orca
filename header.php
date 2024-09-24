<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 *
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>
        <?php bloginfo('name'); // show the blog name, from settings 
        ?> |
        <?php is_front_page() ? bloginfo('description') : wp_title(''); // if we're on the home page, show the description, from the site's settings - otherwise, show the title of the post or page 
        ?>
    </title>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <div id="page" class="site">
        <header id="masthead" class="site-header">
            <nav class="header-navbar py-2">
                <div class="container">
                    <div class="row w-100 align-items-center">
                        <div class="col-auto">
                            <div class="navbar-brand">
                                <?= get_custom_logo() ?>
                            </div>
                        </div>
                        <div class="col d-flex flex-lg-column justify-content-end">
                            <div class="header-right-top d-flex justify-content-end mb-0 mb-lg-3">
                                <ul class="list-inline-icons list-inline d-inline-flex flex-wrap mb-0 align-items-center me-3 me-lg-0">
                                    <li>
                                        <a href="<?= wc_get_cart_url() ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-basket3" viewBox="0 0 16 16">
                                                <path d="M5.757 1.071a.5.5 0 0 1 .172.686L3.383 6h9.234L10.07 1.757a.5.5 0 1 1 .858-.514L13.783 6H15.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H.5a.5.5 0 0 1-.5-.5v-1A.5.5 0 0 1 .5 6h1.717L5.07 1.243a.5.5 0 0 1 .686-.172zM3.394 15l-1.48-6h-.97l1.525 6.426a.75.75 0 0 0 .729.574h9.606a.75.75 0 0 0 .73-.574L15.056 9h-.972l-1.479 6z" />
                                            </svg>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= get_permalink(get_option('woocommerce_myaccount_page_id'));  ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                                            </svg>
                                            <span>
                                                <?php if (is_user_logged_in()) { ?>
                                                    Account
                                                <?php } else { ?>
                                                    Login
                                                <?php } ?>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="https://orca.org.uk/support-orca/make-a-donation">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15" />
                                            </svg>
                                            <span>Donate</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="header-right-bottom d-flex justify-content-end">
                                <form action="/" method="GET">
                                    <input type="text" name="s" class="form-control search w-auto me-2 d-none d-lg-block" id="search" value="<?= isset($_GET['s'])  ? $_GET['s'] : '' ?>" placeholder="Search">
                                </form>
                                <button class="menu-toggler btn btn-link p-0 d-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasHeaderMenu" aria-controls="offcanvasHeaderMenu">
                                    <span>
                                        <span class="menu-toggler-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-water" viewBox="0 0 16 16">
                                                <path d="M.036 3.314a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0L.314 3.964a.5.5 0 0 1-.278-.65m0 3a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0L.314 6.964a.5.5 0 0 1-.278-.65m0 3a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0L.314 9.964a.5.5 0 0 1-.278-.65m0 3a.5.5 0 0 1 .65-.278l1.757.703a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.014-.406a2.5 2.5 0 0 1 1.857 0l1.015.406a1.5 1.5 0 0 0 1.114 0l1.757-.703a.5.5 0 1 1 .372.928l-1.758.703a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.014-.406a1.5 1.5 0 0 0-1.114 0l-1.015.406a2.5 2.5 0 0 1-1.857 0l-1.757-.703a.5.5 0 0 1-.278-.65" />
                                            </svg></span>
                                        <span class="menu-toggler-text">Menu</span>
                                    </span>
                                </button>

                            </div>


                            <div class="offcanvas offcanvas-start d-none" tabindex="-1" id="offcanvasHeaderMenu" aria-labelledby="offcanvasHeaderMenuLabel">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title" id="offcanvasHeaderMenuLabel">Offcanvas</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <div class="dropdown mt-3">
                                        <div id="main-menu">
                                            <?php
                                            wp_nav_menu(array(
                                                'theme_location' => 'header-menu',
                                                'container' => false,
                                                'menu_class' => '',
                                                'fallback_cb' => '__return_false',
                                                'items_wrap' => '<ul id="%1$s" class="navbar-nav me-auto mb-2 mb-md-0 %2$s">%3$s</ul>',
                                                'depth' => 2,
                                                'walker' => new bootstrap_5_wp_nav_menu_walker()
                                            ));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </nav>
        </header><!-- #masthead -->


        <?= do_shortcode('[breadcrumbs]') ?>

        <?php
        $coursecustomemails = get_posts(array(
            'post_type' => 'coursecustomemails',
            'numberposts' => -1,
        ));

        $order_id = 3283;

        foreach ($coursecustomemails as $coursecustomemail) {
            $product_ids = carbon_get_post_meta($coursecustomemail->ID, 'products');

            $in_cart = '';
            foreach ($product_ids as $product_id) {
                $product_is_in_order = bbloomer_check_order_product_id($order_id, $product_id['id']);
                if ($product_is_in_order) {
                    $in_cart .= 'true';
                    $id = $product_is_in_order;
                    $parent = $product_id['id'];
                } else {
                    $in_cart .= 'false';
                }
            }
            if (str_contains($in_cart, 'true')) {
                $order = wc_get_order($order_id);
                $to_email = $order->get_billing_email();
                $title = str_replace(get_the_title($parent), '', get_the_title($id));
                $subject = 'ORCA training course booking';

                $headers = 'From: ORCA <website@orca.org.uk>' . "\r\n";
                echo $coursecustomemail->post_content;
            }
            echo $in_cart;



            $product_ids = array(3255, 3241);
            $in_cart = '';
            foreach ($product_ids as $product_id) {
                $product_is_in_order = bbloomer_check_order_product_id($order_id, $product_id);
                if ($product_is_in_order) {
                    $in_cart .= 'true';
                    $id = $product_is_in_order;
                    $parent = $product_id;
                } else {
                    $in_cart .= 'false';
                }
            }
            if (str_contains($in_cart, 'true')) {
                $order = wc_get_order($order_id);
                $to_email = $order->get_billing_email();
                $title = str_replace(get_the_title($parent), '', get_the_title($id));
                $subject = 'ORCA training course booking';
        
                $headers = 'From: ORCA <website@orca.org.uk>' . "\r\n";
                $content = '';
        
            }

            echo $in_cart;

        }
        ?>