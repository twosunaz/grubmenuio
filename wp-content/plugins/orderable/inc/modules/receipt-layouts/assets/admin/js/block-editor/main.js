const {
  addFilter
} = wp.hooks;
addFilter('blocks.registerBlockType', 'orderable/modify-block-supports', function (settings, name) {
  if (!settings?.supports || !name.includes('core/')) {
    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        style: {
          type: 'object',
          default: {
            ...settings.attributes.style?.default,
            color: {
              ...settings.attributes.style?.default?.color,
              text: '#111111'
            }
          }
        }
      }
    };
  }
  return {
    ...settings,
    supports: {
      ...settings.supports,
      align: false,
      anchor: false,
      spacing: {
        margin: true,
        padding: true
      },
      typography: {
        textAlign: true,
        fontSize: false
      },
      color: {
        text: true,
        background: true
      }
    },
    attributes: {
      ...settings.attributes,
      style: {
        type: 'object',
        default: {
          color: {
            text: '#111111'
          }
        }
      }
    }
  };
});
wp.data.dispatch('core/notices').createInfoNotice(wp.i18n.__('This preview will contain real data order once the receipt is generated, and will adapt to the width of your printer page setup. Please ensure you have selected a default layout in Orderable > Settings > Printing.', 'orderable'), {
  isDismissible: false
});