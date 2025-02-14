import { useReducer } from '@wordpress/element';
import reducer from './reducer';
import { initialState } from './default-state';
import { StateContext } from './use-knowledge-base-state';

export function StateProvider( { children, postId, type, selected } ) {
	const [ state, dispatch ] = useReducer(
		reducer,
		initialState( postId, type, selected ),
	);
	return (
		<StateContext.Provider value={ { state, dispatch } }>
			{ children }
		</StateContext.Provider>
	);
}
