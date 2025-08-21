<?php

return [
    "server_error" => "Server Error Occured",
    'please confirm the registeration step2 with the otp' => 'please confirm the registeration step2',
    'code has been sent to your email' => 'code has been sent to your email',
    'your password has been changed success' => 'your password has been changed success',
    'general' => [
        'success' => 'Operation completed successfully.',
        'error' => 'An error occurred.',
        'validation_error' => 'There was a validation error.',
        'not_found' => 'Resource not found.',
        'unauthorized' => 'Unauthorized action.',
        'forbidden' => 'You do not have permission to perform this action.',
        'files_uploaded' => 'Files uploaded successfully.',
        'message_sent' => 'Message sent successfully.',
        'messages_retrieved' => 'Messages retrieved successfully.',
        'type_not_found' => 'Type not found.',
        'deleted_successfully' => 'Deleted successfully.',
    ],

    'users' => [
        'this mail is used before'    => 'this mail is used before',
        'user_not_found'    => 'User not found.',
        'user_created'      => 'User created successfully.',
        'user_updated'      => 'User updated successfully.',
        'user_deleted'      => 'User deleted successfully.',
        'admin_created'     => 'Admin created successfully.',
        'moderator_created' => 'Moderator created successfully.',
        "moderator_updated" => "Moderator updated successfully",
        'phone_taken'       => 'The phone has already been taken.',
        'otp_sent_successfully' => 'OTP sent successfully.',
    ],

    'authentication' => [
        'login_success' => 'Logged in successfully.',
        'logout_success' => 'Logged out successfully.',
        'invalid_credentials' => 'Invalid login credentials.',
        'account_blocked' => 'This account has been blocked.',
    ],

    'captains' => [
        'captain_not_found'         => 'Captain not found.',
        'captain_status_updated'    => 'Captain status updated successfully.',
        'captain_already_active'    => 'Captain is already active.',
        'captain_balance_updated'   => 'Captain balance updated successfully.',
        'captain_withdraw_success'  => 'Withdrawal made successfully by :admin.',
        'status_updated_successfully'=> 'Availability status updated successfully.',
    ],

    'transactions' => [
        'transaction_created'           => 'Transaction created successfully.',
        'transaction_failed'            => 'Transaction failed.',
        'withdrawal_success'            => 'Withdrawal made successfully.',
        'balance_adjusted'              => 'Balance adjusted successfully.',
        'insufficient_balance'          => 'Insufficient balance for this operation.',
        'withdrawal_admin_description'  => 'Withdrawal made by :admin.',
        'updated_successfully'          => 'Transaction updated successfully.',
        'deleted_successfully'          => 'Transaction deleted successfully.',
        'not_found'                     => 'Transaction not found.',
        'transaction_canceled_or_no_token' => 'Transaction canceled or no token received.',
    ],

    'rides' => [
        'ride_not_found'            => 'Ride not found.',
        'ride_status_updated'       => 'Ride status updated successfully.',
        'ride_completed'            => 'Ride completed successfully.',
        'ride_cancelled'            => 'Ride cancelled successfully.',
        'nearby_ride_requests_found'=> 'Nearby ride requests found.',
        'insufficient_balance'      => "You can't take a ride if your balance is lower than 500.",
        'cannot_accept_new_ride'    => "Can't accept a new ride, you are on an active ride.",
        'request_not_available'     => 'This request is not available.',
        'no_ongoing_rides'          => "You don't have any ongoing rides.",
        'active_ride_retrieved'     => 'Active ride retrieved.',
        // 'ride_status_updated'       => 'Ride status updated.',
        'cannot_cancel_after_pickup'=> "You can't cancel the ride after pick up.",
        'history_retrieved'         => 'Rides history retrieved.',
        'no_pending_ride_requests'  => 'No pending ride requests found for this user.',
    ],

    'orders' => [
        'order_not_found' => 'Order not found.',
        'order_created'   => 'Order created successfully.',
        'order_canceled'   => 'Order canceled successfully.',
        'order_updated'   => 'Order updated successfully.',
        'order_deleted'   => 'Order deleted successfully.',
    ],

    'validation' => [
        'invalid_status' => 'Invalid status provided.',
        'required_field' => 'The :field field is required.',
        'invalid_type'   => 'The :type is invalid.',
        'invalid_amount' => 'The amount must be a positive number.',
    ],

    'withdrawals' => [
        'withdrawal_requested' => 'Withdrawal requested successfully.',
        'withdrawal_failed'    => 'Withdrawal failed.',
        'withdrawal_completed' => 'Withdrawal completed successfully.',
    ],

    'dashboard' => [
        'data_loaded' => 'Dashboard data loaded successfully.',
        'data_failed' => 'Failed to load dashboard data.',
        'top_captains_loaded' => 'top captains loaded.',
    ],

    'filters' => [
        'name_filter_applied' => 'Name filter applied.',
        'type_filter_applied' => 'Type filter applied.',
        'no_results'          => 'No results found.',
    ],

    'types' => [
        'no_new_types_to_attach' => 'No new types to attach to the captain.',
        'attached_successfully' => 'Types attached successfully to the captain.',
        'retrieved_successfully' => 'Types retrieved successfully.',
        'updated_successfully' => 'Types updated successfully.',
        'type_not_found' => 'Type not found.',
    ],

    'promocodes' => [
        'updated_successfully' => 'Promocode updated successfully.',
        'not_found' => 'Promocode not found.',
        'deleted_successfully' => 'Promocode deleted successfully.',
        'not_found_or_inactive' => 'Promocode not found or inactive.',
    ],

    'fcm' => [
        'token_updated' => 'FCM token updated successfully.',
    ],

    'chats' => [
        'no_access' => 'You don\'t have access to this chat.',
    ],

    'payments' => [
        'submitted' => 'Payment submitted.',
        'balance_recharge_done' => 'Balance recharge done successfully.',
        'deposit_request_sent' => 'Deposit request sent.',
    ],

    'location' => [
        'updated_successfully' => 'Location updated successfully.',
    ],

];
