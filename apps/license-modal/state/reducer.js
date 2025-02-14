export default function reducer( state, action ) {
	switch ( action.type ) {
		case 'SET_TOGGLE_MODAL':
			return { ...state, isModalOpen: action.payload };
		default:
			return state;
	}
}
