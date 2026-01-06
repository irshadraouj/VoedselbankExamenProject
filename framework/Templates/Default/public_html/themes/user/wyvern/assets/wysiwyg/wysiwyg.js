CKEDITOR.stylesSet.add('wyvern', [
    // Block Styles
    { name: 'Blue Title',       element: 'h3',      styles: { 'color': 'Blue' } },
    { name: 'Red Title',        element: 'h3',      styles: { 'color': 'Red' } },

    // Inline Styles
    { name: 'Marker: Yellow',   element: 'span',    styles: { 'background-color': 'Yellow' } },
    { name: 'Marker: Green',    element: 'span',    styles: { 'background-color': 'Lime' } },

    // Object Styles
    {
        name: 'Image on Left',
        element: 'img',
        attributes: {
            style: 'padding: 5px; margin-right: 5px',
            border: '2',
            align: 'left'
        }
    },
    {
        name: 'Image on Right',
        element: 'img',
        attributes: {
            style: 'padding: 5px; margin-right: 5px',
            border: '2',
            align: 'right'
        }
    }
]);
