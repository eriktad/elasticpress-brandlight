window.bl_ep_autosuggest_link_pattern = function (value, option, hit) {
    const {text, url} = option;
    const escapedText = text.replace(/\\([\s\S])|(")/g, '&quot;')

    const searchParts = value.trim().split(' ');
    let resultsText = escapedText;
    // uses some regex magic to match upper/lower/capital case
    searchParts.forEach((word) => {
        const regex = new RegExp(`(${word.trim()})`, 'gi');
        if (word.length > 1) {
            resultsText = resultsText.replace(
                regex,
                `<span class="ep-autosuggest-highlight">$1</span>`,
            );
        }
    });
    let thumbnail_path = '';
    if (Object.prototype.hasOwnProperty.call(hit, '_source')
        && Object.prototype.hasOwnProperty.call(hit._source, 'meta')
        && Object.prototype.hasOwnProperty.call(hit._source.meta, 'images')
        && Object.prototype.hasOwnProperty.call(hit._source.meta.images, 'thumbnail')
        && typeof hit._source.meta.images.thumbnail !== 'undefined'
        && Object.prototype.hasOwnProperty.call(hit._source.meta.images.thumbnail, 'value')
    ) {
        thumbnail_path = hit._source.meta.images.thumbnail.value;
    }
    
    return `<li class="autosuggest-item" role="option" aria-selected="false">
				<a href="${url}" class="autosuggest-link" data-search="${escapedText}" data-url="${url}">
					<img src="${thumbnail_path}" style="width: 30px;height: 30px;" alt="${escapedText}">
                    ${resultsText}
				</a>
			</li>`;
}