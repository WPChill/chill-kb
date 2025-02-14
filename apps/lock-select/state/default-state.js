export const initialState = ( postId, type, selected ) => ( {
	postId,
	selectedProducts: JSON.parse( selected ),
	selectedType: type,
} );
