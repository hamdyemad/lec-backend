<?php

return [
    "server_error" => "حدث خطأ في الخادم" ,
    'please confirm the registeration step2 with the otp' => 'برجاء استكمال التسجيل في الخطوة التالية',


    'general' => [
        'success'           => 'تمت العملية بنجاح.',
        'error'             => 'حدث خطأ ما.',
        'validation_error'  => 'هناك خطأ في التحقق.',
        'not_found'         => 'المورد غير موجود.',
        'unauthorized'      => 'الإجراء غير مصرح به.',
        'forbidden'         => 'ليس لديك إذن لتنفيذ هذا الإجراء.',
        'files_uploaded'    => 'تم رفع الملفات بنجاح.',
        'message_sent'      => 'تم إرسال الرسالة بنجاح.',
        'messages_retrieved'=> 'تم استرجاع الرسائل بنجاح.',
        'deleted_successfully' => 'تم الحذف بنجاح.'
    ],

    'users' => [
        'this mail is used before'    => 'هذا الايميل مستخدم من قبل',
        'user_not_found'    => 'المستخدم غير موجود.',
        'user_created'      => 'تم إنشاء المستخدم بنجاح.',
        'user_updated'      => 'تم تحديث المستخدم بنجاح.',
        'user_deleted'      => 'تم حذف المستخدم بنجاح.',
        'admin_created'     => 'تم إنشاء المدير بنجاح.',
        'moderator_created' => 'تم إنشاء المشرف بنجاح.',
        "moderator_updated" => "تم تحديث بيانات المشرف بنجاح",
        'phone_taken'       => 'رقم الهاتف مستخدم بالفعل.',
        'otp_sent_successfully' => 'تم إرسال رمز التحقق بنجاح.',
    ],

    'authentication' => [
        'login_success'         => 'تم تسجيل الدخول بنجاح.',
        'logout_success'        => 'تم تسجيل الخروج بنجاح.',
        'invalid_credentials'   => 'بيانات تسجيل الدخول غير صحيحة.',
        'account_blocked'       => 'تم حظر هذا الحساب.',
    ],

    'captains' => [
        'captain_not_found'         => 'السائق غير موجود.',
        'captain_status_updated'    => 'تم تحديث حالة السائق بنجاح.',
        'captain_already_active'    => 'السائق نشط بالفعل.',
        'captain_balance_updated'   => 'تم تحديث رصيد السائق بنجاح.',
        'captain_withdraw_success'  => 'تم إجراء السحب بنجاح بواسطة :admin.',
        'status_updated_successfully' => 'تم تحديث حالة التوفر بنجاح.',
    ],

    'transactions' => [
        'transaction_created'           => 'تم إنشاء المعاملة بنجاح.',
        'transaction_failed'            => 'فشلت المعاملة.',
        'withdrawal_success'            => 'تم السحب بنجاح.',
        'balance_adjusted'              => 'تم تعديل الرصيد بنجاح.',
        'insufficient_balance'          => 'الرصيد غير كافٍ لهذه العملية.',
        'withdrawal_admin_description'  => 'تم السحب بواسطة :admin.',
        'updated_successfully'          => 'تم تحديث المعاملة بنجاح.',
        'deleted_successfully'          => 'تم حذف المعاملة بنجاح.',
        'not_found'                     => 'المعاملة غير موجودة.',
        'transaction_canceled_or_no_token'=> 'تم إلغاء المعاملة أو لم يتم استلام رمز.',
    ],

    'rides' => [
        'ride_not_found'            => 'الرحلة غير موجودة.',
        'ride_status_updated'       => 'تم تحديث حالة الرحلة بنجاح.',
        'ride_completed'            => 'تم إكمال الرحلة بنجاح.',
        'ride_cancelled'            => 'تم إلغاء الرحلة بنجاح.',
        'nearby_ride_requests_found'=> 'تم العثور على طلبات ركوب قريبة.',
        'insufficient_balance'      => 'لا يمكنك قبول الركوب إذا كان رصيدك أقل من 500.',
        'cannot_accept_new_ride'    => 'لا يمكنك قبول رحلة جديدة، أنت في رحلة نشطة.',
        'request_not_available'     => 'هذا الطلب غير متاح.',
        'no_ongoing_rides'          => 'ليس لديك أي رحلات جارية.',
        'active_ride_retrieved'     => 'تم استرجاع الرحلة النشطة.',
        // 'ride_status_updated'       => 'تم تحديث حالة الرحلة.',
        'cannot_cancel_after_pickup'=> 'لا يمكنك إلغاء الرحلة بعد الاستلام.',
        'history_retrieved'         => 'تم استرجاع سجل الرحلات.',
        'no_pending_ride_requests'  => 'لا توجد طلبات رحلات معلقة لهذا المستخدم.',
    ],

    'orders' => [
        'order_not_found'   => 'الطلب غير موجود.',
        'order_created'     => 'تم إنشاء الطلب بنجاح.',
        'order_canceled'    => 'تم إلغاء الطلب.',
        'order_updated'     => 'تم تحديث الطلب بنجاح.',
        'order_deleted'     => 'تم حذف الطلب بنجاح.',
    ],

    'validation' => [
        'invalid_status'    => 'تم تقديم حالة غير صحيحة.',
        'required_field'    => 'حقل :field مطلوب.',
        'invalid_type'      => ':type غير صالح.',
        'invalid_amount'    => 'يجب أن يكون المبلغ رقماً موجباً.',
    ],

    'withdrawals' => [
        'withdrawal_requested'  => 'تم طلب السحب بنجاح.',
        'withdrawal_failed'     => 'فشل طلب السحب.',
        'withdrawal_completed'  => 'تم إكمال السحب بنجاح.',
    ],

    'dashboard' => [
        'data_loaded' => 'تم تحميل بيانات لوحة التحكم بنجاح.',
        'data_failed' => 'فشل تحميل بيانات لوحة التحكم.',
        'top_captains_loaded' => ' تحميل بيانات القادة.',
    ],

    'filters' => [
        'name_filter_applied'   => 'تم تطبيق تصفية الاسم.',
        'type_filter_applied'   => 'تم تطبيق تصفية النوع.',
        'no_results'            => 'لم يتم العثور على نتائج.',
    ],

    'types' => [
        'no_new_types_to_attach' => 'لا توجد أنواع جديدة لإرفاقها بالسائق.',
        'attached_successfully' => 'تم إرفاق الأنواع بالسائق بنجاح.',
        'retrieved_successfully' => 'تم استرجاع الأنواع بنجاح.',
        'updated_successfully' => 'تم تحديث الأنواع بنجاح.',
        'type_not_found' => 'النوع غير موجود.',
    ],

    'promocodes' => [
        'updated_successfully' => 'تم تحديث الكود الترويجي بنجاح.',
        'not_found' => 'الكود الترويجي غير موجود.',
        'deleted_successfully' => 'تم حذف الكود الترويجي بنجاح.',
        'not_found_or_inactive' => 'الكود الترويجي غير موجود أو غير مفعل.',
    ],

    'fcm' => [
        'token_updated' => 'تم تحديث رمز FCM بنجاح.',
    ],

    'chats' => [
        'no_access' => 'ليس لديك إذن للدخول إلى هذه المحادثة.',
    ],

    'payments' => [
        'submitted' => 'تم إرسال الدفع.',
        'deposit_request_sent' => 'تم إرسال طلب الإيداع.',
        'balance_recharge_done' => 'تم إعادة شحن الرصيد بنجاح.',
    ],

    'location' => [
        'updated_successfully' => 'تم تحديث الموقع بنجاح.',
    ],
];
