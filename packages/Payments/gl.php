<?php
	$gateway=gs_var_storage::load('payments_gateway');
	gs_session::save($gateway,'payments_gateway');
	switch($alias) {
		case 'payment_approved':
			return '/Payments/approved';
			break;
		case 'payment_declined':
			return '/Payments/declined';
			break;
		case 'payment_error':
			return '/Payments/error';
			break;
	}
