import { useReducer } from '@wordpress/element';
import reducer from './reducer';
import { initialState } from './default-state';
import { StateContext } from './use-knowledge-base-state';

export function StateProvider( { children, licenses } ) {
	const [ state, dispatch ] = useReducer(
		reducer,
		initialState( licenses ),
	);
	return (
		<StateContext.Provider value={ { state, dispatch } }>
			{ children }
		</StateContext.Provider>
	);
}
