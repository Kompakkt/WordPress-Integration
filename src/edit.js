import {__} from '@wordpress/i18n';
import {useBlockProps} from '@wordpress/block-editor';
import {useState, useEffect} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {SelectControl} from '@wordpress/components';
import './editor.scss';

export function edit({attributes: {resource, endpoint, width, height}, setAttributes}) {
	const [models, setModels] = useState([]);
	const [selectedModel, setSelectedModel] = useState(null);

	useEffect(() => {
		apiFetch({path: '/kompakkt/v1/models'}).then(models => {
			setModels(models);
			setSelectedModel(models[0].id);
		});
	}, []);

	// When selectedModel updates, get the first file
	useEffect(() => {
		const model = models.find(model => model.id === selectedModel);
		if (model?.files) {
			const files = JSON.parse(model.files);
			const firstValidFile = files[0];
			const resource = firstValidFile.split('/').pop();
			const endpoint = `${location.origin}/index.php?rest_route=/kompakkt/v1/model&id=${selectedModel}`;
			setAttributes({resource, endpoint});
		}
	}, [selectedModel]);

	return (
		<div {...useBlockProps()}>
			<SelectControl
				label={__('Select a Model', 'kompakkt')}
				options={models.map(model => ({label: model.title, value: model.id}))}
				onChange={setSelectedModel}
			/>
			{resource && <app-kompakkt resource={resource} endpoint={endpoint} style={{width, height}}></app-kompakkt>}
		</div>
	);
}

export function save({attributes: {resource, endpoint, width, height}}) {
	return (
		<div {...useBlockProps.save()}>
			<app-kompakkt resource={resource} endpoint={endpoint} style={{width, height}}></app-kompakkt>
		</div>
	);
}
