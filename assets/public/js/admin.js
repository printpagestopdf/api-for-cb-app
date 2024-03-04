jQuery(
	function ($) {

		$( '#cbappapi_select_authroles' ).multiselect(
			{
				optionAttributes: ['name'],
				maxWidth: 300,
				maxPlaceholderWidth: 500,
				checkboxAutoFit: true,
			}
		);

		$( '#cbappapi_select_auth_variants' ).change(
			(event) => {
            console.log( $( event.currentTarget ).val() );
            $( '#cbappapi_select_authroles' ).multiselect( 'disable', ($( event.currentTarget ).val() != 'restrict') );
			}
		);

		$( '#cbappapi_select_authroles' ).multiselect( 'disable', ($( '#cbappapi_select_auth_variants' ).val() != 'restrict') );
	}
);