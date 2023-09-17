import {__} from '@wordpress/i18n';
import {useBlockProps} from '@wordpress/block-editor';
import {useState, useEffect} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {SelectControl} from '@wordpress/components';
import './editor.scss';

export function edit({attributes: {instance, resource, endpoint, width, height, selectedModel}, setAttributes}) {
	const [models, setModels] = useState([]);
	const [src, setSrc] = useState(new URL(instance));

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

	useEffect(() => {
		const src = new URL(instance);
		src.searchParams.set('resource', resource);
		src.searchParams.set('endpoint', endpoint);
		src.searchParams.set('standalone', 'true');
		src.searchParams.set('mode', 'upload');
		setSrc(src);
	}, [instance, resource, endpoint]);

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

	useEffect(() => {
		window.addEventListener('message', (event) => {
			if (event.origin === src.origin) {
				const data = event.data;
				// TODO: Handle settings and annotations sent from the viewer
				console.log('Received message from kompakkt', data);
			}
		})
	}, []);

	// Key to reload app-kompakkt when the properties change
	const key = `${instance}-${resource}-${endpoint}`;

	return (<div {...useBlockProps()}>
		<SelectControl
			label={__('Select a Model', 'kompakkt')}
			options={models.map(model => ({label: model.title, value: model.id}))}
			value={selectedModel}
			onChange={value => setAttributes({selectedModel: value})}
		/>

		<iframe key={key} src={src.toString()} allowFullScreen={true} style={{width, height, border: 'none', borderRadius: '8px'}}></iframe>
	</div>);
}

export function save({attributes: {instance, resource, endpoint, width, height}}) {
	return (<div {...useBlockProps.save()}>
		<app-kompakkt instance={instance} resource={resource} endpoint={endpoint}
					  style={{width, height}}></app-kompakkt>
	</div>);
}
