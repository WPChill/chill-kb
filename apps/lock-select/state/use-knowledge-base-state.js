import { createContext, useContext } from '@wordpress/element';
export const StateContext = createContext();

export function useKnowledgeBaseState() {
	const context = useContext( StateContext );
	if ( ! context ) {
		throw new Error( 'useKnowledgeBaseState must be used within a StateProvider' );
	}
	return context;
}
