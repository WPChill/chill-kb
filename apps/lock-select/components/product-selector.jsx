import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { useAccessProducts } from '../query/useAccessProducts';
import ProductTypeSelector from './product-type-selector';

export default function ProductSelector() {
	const { state } = useKnowledgeBaseState();
	const { postId, selectedProducts } = state;
	const { data, error } = useAccessProducts( postId );

	if ( error || ! data ) {
		return null;
	}

	return (
		<>
			{ data.map( ( productType ) => (
				<ProductTypeSelector
					key={ productType.slug }
					productType={ productType }
				/>
			) ) }
			<input
				type="hidden"
				name={ `wpchill_kb_access_products` }
				value={ JSON.stringify( selectedProducts ) }
			/>
		</>
	);
}
