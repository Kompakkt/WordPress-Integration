import {__} from '@wordpress/i18n';
import {useBlockProps} from '@wordpress/block-editor';
import {useState, useEffect} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {SelectControl} from '@wordpress/components';
import './editor.scss';

export function edit({attributes: {instance, resource, endpoint, width, height, selectedModel}, setAttributes}) {
	const [models, setModels] = useState([]);

	useEffect(() => {
		apiFetch({path: '/kompakkt/v1/models'}).then(models => {
			setModels(models);
			if (selectedModel.length === 0) {
				setAttributes({selectedModel: models[0].id});
			}
		});
	}, []);

	useEffect(() => {
		apiFetch({path: '/kompakkt/v1/instance-url'}).then(instance => {
			instance = instance || 'https://kompakkt.de/viewer/index.html';
			setAttributes({instance});
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

	// Key to reload app-kompakkt when the properties change
	const key = `${instance}-${resource}-${endpoint}`;

	return (<div {...useBlockProps()}>
		<SelectControl
			label={__('Select a Model', 'kompakkt')}
			options={models.map(model => ({label: model.title, value: model.id}))}
			value={selectedModel}
			onChange={value => setAttributes({selectedModel: value})}
		/>

		<app-kompakkt key={key} instance={instance} resource={resource} endpoint={endpoint}
					  style={{width, height}}></app-kompakkt>
	</div>);
}

export function save({attributes: {instance, resource, endpoint, width, height}}) {
	return (<div {...useBlockProps.save()}>
		<app-kompakkt instance={instance} resource={resource} endpoint={endpoint}
					  style={{width, height}}></app-kompakkt>
	</div>);
}
