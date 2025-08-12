# Chair Texture Swap

WordPress plugin that batches chair upholstery texture swaps using the OpenAI image API. Includes admin pages for processing images, settings, and log viewing.

### Timeout handling

The plugin now uses a 60 second default timeout for requests to the OpenAI API and retries once with a longer timeout if the initial request fails with a `cURL error 28`. You can adjust the timeout value from the plugin settings page.
