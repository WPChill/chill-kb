export default function reducer( state, action ) {
	switch ( action.type ) {
		case 'SET_SELECTED_PRODUCTS':
			return { ...state, selectedProducts: action.payload };
		case 'SET_SELECTED_TYPE':
			return { ...state, selectedType: action.payload };
		default:
			return state;
	}
}
