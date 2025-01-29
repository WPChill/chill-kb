import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const fetchModalLicenses = async ( postId ) => {
	const products = await apiFetch( {
		path: `/wpchill-kb/v1/modal-licenses/${ postId }`,
		method: 'GET',
	} );

	return products;
};

export const useModalLicenses = () => {
	const { state } = useKnowledgeBaseState();

	const data = useQuery( {
		queryKey: [ 'modalLicenses', state.postId ],
		queryFn: async () => fetchModalLicenses( state.postId ),
	} );

	return data;
};
