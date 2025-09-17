<?php
function add_company_column( $columns ) {
	$columns['company'] = 'Company';

	return $columns;
}

add_filter( 'manage_users_columns', 'add_company_column' );

function show_company_column_content( $value, $column_name, $user_id ) {
	if ( $column_name == 'company' ) {
		$company = get_user_meta( $user_id, 'company', true );

		return ! empty( $company ) ? esc_html( $company ) : 'N/A';
	}

	return $value;
}

add_filter( 'manage_users_custom_column', 'show_company_column_content', 10, 3 );

function make_company_column_sortable( $sortable_columns ) {
	$sortable_columns['company'] = 'company';

	return $sortable_columns;
}

add_filter( 'manage_users_sortable_columns', 'make_company_column_sortable' );

function sort_users_by_company( $query ) {
	global $wpdb;

	if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] == 'company' ) {
		$query->query_from    .= " LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = 'company'";
		$query->query_orderby = " ORDER BY $wpdb->usermeta.meta_value " . esc_sql( $query->query_vars['order'] );
	}
}

add_action( 'pre_user_query', 'sort_users_by_company' );
