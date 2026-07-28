<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'home';
$route['404_override']       = 'home/page_not_found';

// YounGo Arabic-default public frontend aliases.
$route['home/blog'] = 'blog/index';
$route['home/contact'] = 'home/contact_us';
$route['subscriptions'] = 'home/subscriptions';

// YounGo English public frontend aliases.
$route['en'] = 'home/index';
$route['en/home'] = 'home/index';
$route['en/subscriptions'] = 'home/subscriptions';
$route['en/home/courses'] = 'home/courses';
$route['en/home/courses/(:num)'] = 'home/courses';
$route['en/home/course/(:any)/(:num)'] = 'home/course/$1/$2';
$route['en/home/instructor_page/(:num)'] = 'home/instructor_page/$1';
$route['en/home/search'] = 'home/search';
$route['en/home/search/(:any)'] = 'home/search/$1';
$route['en/home/blog'] = 'blog/index';
$route['en/blog/details/(:any)/(:num)'] = 'blog/details/$1/$2';
$route['en/home/contact'] = 'home/contact_us';
$route['en/home/my_courses'] = 'home/my_courses';
$route['en/home/my_access'] = 'home/my_access';
$route['en/home/my_wishlist'] = 'home/my_wishlist';
$route['en/login'] = 'login/index';
$route['en/login/forgot_password_request'] = 'login/forgot_password_request';
$route['en/sign-up'] = 'sign_up/index';
$route['en/sign_up'] = 'sign_up/index';

// YounGo Arabic compatibility public frontend aliases.
$route['ar'] = 'home/index';
$route['ar/home'] = 'home/index';
$route['ar/subscriptions'] = 'home/subscriptions';
$route['ar/home/courses'] = 'home/courses';
$route['ar/home/courses/(:num)'] = 'home/courses';
$route['ar/home/course/(:any)/(:num)'] = 'home/course/$1/$2';
$route['ar/home/instructor_page/(:num)'] = 'home/instructor_page/$1';
$route['ar/home/search'] = 'home/search';
$route['ar/home/search/(:any)'] = 'home/search/$1';
$route['ar/home/blog'] = 'blog/index';
$route['ar/home/contact'] = 'home/contact_us';
$route['ar/home/my_courses'] = 'home/my_courses';
$route['ar/home/my_access'] = 'home/my_access';
$route['ar/home/my_wishlist'] = 'home/my_wishlist';
$route['ar/courses'] = 'home/courses';
$route['ar/courses/(:num)'] = 'home/courses';
$route['ar/course/(:any)/(:num)'] = 'home/course/$1/$2';
$route['ar/search'] = 'home/search';
$route['ar/search/(:any)'] = 'home/search/$1';
$route['ar/blog'] = 'blog/index';
$route['ar/blog/details/(:any)/(:num)'] = 'blog/details/$1/$2';
$route['ar/contact'] = 'home/contact_us';
$route['ar/my-courses'] = 'home/my_courses';
$route['ar/my-access'] = 'home/my_access';
$route['ar/wishlist'] = 'home/my_wishlist';
$route['ar/login'] = 'login/index';
$route['ar/login/forgot_password_request'] = 'login/forgot_password_request';
$route['ar/sign-up'] = 'sign_up/index';

$route['certificate/(:any)'] = "addons/certificate/generate_certificate/$1";

// YounGo subscription plan management.
$route['admin/youngo/subscription-plans'] = 'youngo_subscription_plans/index';
$route['admin/youngo/subscription-plans/create'] = 'youngo_subscription_plans/create';
$route['admin/youngo/subscription-plans/(:num)'] = 'youngo_subscription_plans/view/$1';
$route['admin/youngo/subscription-plans/(:num)/edit'] = 'youngo_subscription_plans/edit/$1';
$route['admin/youngo/subscription-plans/(:num)/status'] = 'youngo_subscription_plans/status/$1';
$route['admin/youngo/subscription-plans/(:num)/archive'] = 'youngo_subscription_plans/archive/$1';
$route['admin/youngo/subscription-plans/(:num)/restore'] = 'youngo_subscription_plans/restore/$1';

// YounGo language pack preview.
$route['admin/youngo/language/arabic-import-preview'] = 'admin/youngo_arabic_pack_import_preview';
$route['admin/youngo/language/arabic-import-apply'] = 'admin/youngo_arabic_pack_import_apply';
$route['admin/youngo/language/edit-phrase-data'] = 'admin/youngo_edit_phrase_paginated_data';

// YounGo manual grants.
$route['admin/youngo/manual-grants'] = 'youngo_manual_grants/index';
$route['admin/youngo/manual-grants/create'] = 'youngo_manual_grants/create';
$route['admin/youngo/manual-grants/(:num)'] = 'youngo_manual_grants/view/$1';
$route['admin/youngo/manual-grants/(:num)/revoke'] = 'youngo_manual_grants/revoke/$1';

// YounGo role assignments.
$route['admin/youngo/role-assignments'] = 'youngo_role_assignments/index';
$route['admin/youngo/role-assignments/update'] = 'youngo_role_assignments/update';

// YounGo read-only payment settings summary.
$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';
$route['admin/youngo/payment-settings/test'] = 'youngo_payment_settings/test';
$route['admin/youngo/checkout-coupon-usage'] = 'youngo_checkout_coupon_usage/index';
$route['admin/youngo/instapay-payments'] = 'youngo_instapay_payments/index';
$route['admin/youngo/instapay-payments/(:num)'] = 'youngo_instapay_payments/view/$1';
$route['admin/youngo/instapay-payments/(:num)/approve'] = 'youngo_instapay_payments/approve/$1';
$route['admin/youngo/instapay-payments/(:num)/reject'] = 'youngo_instapay_payments/reject/$1';
$route['admin/youngo/instapay-payments/(:num)/evidence'] = 'youngo_instapay_payments/evidence/$1/preview';
$route['admin/youngo/instapay-payments/(:num)/evidence/download'] = 'youngo_instapay_payments/evidence/$1/download';

// YounGo disabled Paymob webhook skeleton.
$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';
$route['payment/paymob/return'] = 'youngo_payment_return/paymob';

// YounGo disabled local checkout route skeletons.
$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start/$1';
$route['youngo/checkout/subscription/start/(:num)'] = 'youngo_checkout/start_subscription/$1';
$route['youngo/checkout/order/(:any)'] = 'youngo_checkout/order/$1';
$route['youngo/checkout/return/(:any)'] = 'youngo_checkout/return/$1';
$route['youngo/checkout/status/(:any)'] = 'youngo_checkout/status/$1';
$route['youngo/checkout/coupon/apply/(:any)'] = 'youngo_checkout/apply_coupon/$1';
$route['youngo/checkout/coupon/clear/(:any)'] = 'youngo_checkout/clear_coupon/$1';
$route['youngo/checkout/zero-coupon/complete/(:any)'] = 'youngo_checkout/complete_zero_amount_coupon/$1';
$route['youngo/checkout/instapay/submit/(:any)'] = 'youngo_checkout/submit_instapay/$1';

//course bundles
$route['course_bundles/(:any)']               = "addons/course_bundles/index/$1";
$route['course_bundles']                      = "addons/course_bundles";
$route['course_bundles/search/(:any)']        = "addons/course_bundles/search/$1";
$route['course_bundles/search/(:any)/(:any)'] = "addons/course_bundles/search/$1/$1";
$route['bundle_details/(:any)/(:any)']        = "addons/course_bundles/bundle_details/$1";
$route['bundle_details/(:any)']               = "addons/course_bundles/bundle_details/$1/$1";
$route['course_bundles/buy/(:any)']           = "addons/course_bundles/buy/$1";
$route['home/my_bundles']                     = "addons/course_bundles/my_bundles";
$route['home/bundle_invoice/(:any)']          = "addons/course_bundles/invoice/$1";
//end course bundles

//ebook
$route['ebook/ebook_details/(:any)/(:any)'] = "addons/ebook/ebook_details/$1/$2";
$route['ebook']                             = "addons/ebook/ebooks";
$route['ebook_manager/all_ebooks']          = "addons/ebook_manager/all_ebooks";
$route['ebook_manager/add_ebook']           = "addons/ebook_manager/add_ebook";
$route['ebook_manager/payment_history']     = "addons/ebook_manager/payment_history";
$route['ebook_manager/category']            = "addons/ebook_manager/category";
$route['ebook/buy/(:any)']                  = "addons/ebook/buy/$1";
$route['home/my_ebooks']                    = "addons/ebook/my_ebooks";
//end ebook

//BLog
$route['blogs']        = "blog/blogs";
$route['blogs/(:any)'] = "blog/blogs/$1";
//End blog

//Contact
$route['contact'] = 'home/contact_us';
//End contact

//Custom page
$route['page/(:any)'] = "page/index/$1";
//End Custom page

//tutor booking ..... tutor_booking/tutors
$route['tutors']                    = "addons/tutor_booking/list_of_tuitions";
$route['tutors/(:any)']             = "addons/tutor_booking/list_of_tuitions/$1";
$route['tutor/filter']              = "addons/tutor_booking/list_of_tuitions_after_filter";
$route['schedules_bookings/(:any)'] = "addons/tutor_booking/tutor_details/$1";
$route['my_bookings']               = "addons/tutor_booking/booked_schedules_student";
//End tutor booking

$route['sitemap.xml'] = 'sitemap';

$route['translate_uri_dashes'] = false;
