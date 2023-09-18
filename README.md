<p align="center">
	<img src="https://raw.githubusercontent.com/Kompakkt/Assets/main/kompakkt-wordpress-logo.png" alt="Kompakkt Logo" width="600">
</p>

# What?

This is a proof-of-concept plugin to showcase the Kompakkt Viewer as a block-component for WordPress.
When installed, the plugin adds:
- a new "Kompakkt"-block to the block editor
- admin menus to upload and manage models

# Development notes

1. Install the required dependencies
```
npm install
```

2. Start the wp-env WordPress development environment
```
npm run wp-env start
```

3. Start the development scripts
```
npm run start
```

4. Visit the WordPress instance at http://localhost:8888/wp-admin, log in using the username "admin" and the password "password"


5. When done, stop the development environment
```
npm run wp-env stop
```

# Roadmap

- [x] Add a "Kompakkt"-block to the block editor
- [x] Add admin menus to upload and manage models
- [x] Add option to adjust viewer settings from inside the plugin
- [ ] Add option to annotate models from inside the plugin
- [ ] Add support for all Kompakkt model types
