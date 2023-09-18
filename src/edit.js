import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import { useState, useEffect, useCallback } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import { SelectControl, Button } from "@wordpress/components";
import "./editor.scss";

export function edit({
	attributes: {
		instance,
		resource,
		endpoint,
		width,
		height,
		selectedModel,
		src,
	},
	setAttributes,
}) {
	const [models, setModels] = useState([]);
	const [iframeKey, setIframeKey] = useState(0);
	const [showIframe, setShowIframe] = useState(false);

	useEffect(() => {
		apiFetch({ path: "/kompakkt/v1/models" }).then(async (models) => {
			setModels(models);
			setAttributes({
				selectedModel:
					selectedModel.length === 0 ? models[0].id : selectedModel,
			});
		});
	}, []);

	useEffect(() => {
		apiFetch({ path: "/kompakkt/v1/instance-url" }).then((instance) => {
			instance = instance || "https://kompakkt.de/viewer/index.html";
			setAttributes({ instance });
		});
	}, []);

	const handleLoadClick = useCallback(async () => {
		const model = models.find((model) => model.id === selectedModel);
		if (!model?.files) return;
		const files = JSON.parse(model.files);
		const firstValidFile = files[0];
		const resource = firstValidFile.split("/").pop();
		const endpoint = `${location.origin}/index.php?rest_route=/kompakkt/v1/model&id=${selectedModel}`;

		const src = new URL(instance);
		src.searchParams.set("resource", resource);
		src.searchParams.set("endpoint", endpoint);
		src.searchParams.set("standalone", "true");
		src.searchParams.set("mode", "open");

		const response = await apiFetch({
			path: addQueryArgs("/kompakkt/v1/model-settings", { id: model.id }),
		});
		const hasSettings = response?.status !== "error";
		if (hasSettings) {
			src.searchParams.set("settings", "settings.json");
		}

		setAttributes({ src: src.toString(), resource, endpoint });
		setIframeKey((prevKey) => prevKey + 1);
		setShowIframe(true);
	}, [instance, models, selectedModel, iframeKey]);

	useEffect(() => {
		const handleMessage = (event) => {
			if (event.origin !== new URL(instance).origin) return;
			// TODO: Handle annotations sent from the viewer
			const message = event.data;
			console.log("Received message from kompakkt", message);

			if (message.type === "settings") {
				apiFetch({
					path: addQueryArgs("/kompakkt/v1/model-settings", {
						id: selectedModel,
					}),
					method: "POST",
					data: JSON.stringify(message.data),
				})
					.then((response) => {
						console.log(response);
						return handleLoadClick();
					})
					.catch((error) => console.log(error));
			}
		};
		window.addEventListener("message", handleMessage);
		return () => {
			window.removeEventListener("message", handleMessage);
		};
	}, [instance, selectedModel, handleLoadClick]);

	return (
		<div {...useBlockProps()}>
			<div
				style={{
					display: "flex",
					justifyContent: "center",
					gap: "8px",
					alignItems: "center",
					width: "610px",
					margin: "0 auto",
				}}
			>
				<div style={{ flexGrow: 1 }}>
					<SelectControl
						label={__("Select a Model", "kompakkt")}
						options={models.map((model) => ({
							label: model.title,
							value: model.id,
						}))}
						value={selectedModel}
						onChange={(value) => setAttributes({ selectedModel: value })}
					/>
				</div>

				<Button onClick={handleLoadClick} variant="primary">
					Load
				</Button>
			</div>

			{src.length > 0 && showIframe && (
				<iframe
					key={iframeKey}
					src={src}
					allowFullScreen={true}
					style={{ width, height, border: "none", borderRadius: "8px" }}
				></iframe>
			)}
		</div>
	);
}

export function save({ attributes: { src, width, height } }) {
	return (
		<div {...useBlockProps.save()}>
			<iframe
				src={src}
				allowFullScreen={true}
				style={{ width, height, border: "none", borderRadius: "8px" }}
			></iframe>
		</div>
	);
}
