import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const fetchAccessProducts = async ( postId ) => {
	const products = await apiFetch( {
		path: `/wpchill-kb/v1/restrictions/${ postId }`,
		method: 'GET',
	} );

	return products;
};

export const useAccessProducts = () => {
	const { state } = useKnowledgeBaseState();

	const data = useQuery( {
		queryKey: [ 'accessProducts', state.postId ],
		queryFn: async () => fetchAccessProducts( state.postId ),
	} );

	return data;
};
