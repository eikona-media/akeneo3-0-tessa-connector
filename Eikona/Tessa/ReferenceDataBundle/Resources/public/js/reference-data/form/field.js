'use strict';

define(
  [
    'pim/form/common/fields/field',
    'underscore',
    'eikona/tessa/connector/reference-data/product/field/tessa',
    'routing',
    'oro/loading-mask'
  ], function (
    BaseField,
    _,
    fieldTemplate,
    Routing,
    LoadingMask) {

    return BaseField.extend({
      fieldTemplate: _.template(fieldTemplate),
      modalBox: null,
      boundReceiveMessage: null,

      events: {
        'click .add-asset': 'openModal',
        'click .js-remove-asset': 'onRemoveAssetClick'
      },

      initialize (...args) {
        this.setOptions([]);
        BaseField.prototype.initialize.apply(this, args);

        this.boundReceiveMessage = this.receiveMessage.bind(this);

        this.modalTemplate = _.template('\
          <div class="AknFullPage">\
            <div class="AknFullPage-content">\
              <div>\
                <div class="AknFullPage-titleContainer">\
                  <% if (typeof subtitle !== \'undefined\') { %>\
                    <div class="AknFullPage-subTitle"><%- subtitle %></div>\
                  <% } %>\
                  <div class="AknFullPage-title"><%- title %></div>\
                  <% if (typeof innerDescription !== \'undefined\') { %>\
                    <div class="AknFullPage-description">\
                      <%- innerDescription %>\
                    </div>\
                  <% } %>\
                </div>\
                <div class="modal-body">\
                  <iframe width="100%" height="100%"></iframe>\
                </div>\
              </div>\
            </div>\
          </div>\
          <div class="AknFullPage-cancel cancel"></div>\
        ');

        return this;
      },

      setOptions (options) {
        this.options = options;
      },

      getOption (name) {
        return typeof this.options[name] !== 'undefined' ? this.options[name] : null;
      },

      renderInput () {
        return this.renderField(this.getAssetIdsArray());
      },

      renderField (values) {
        const assets = values.map((assetId) => ({
          id: assetId,
          url: Routing.generate('eikona_tessa_media_preview', {assetId}),
          linkUrl: Routing.generate('eikona_tessa_media_detail', {assetId})
        }));

        return this.fieldTemplate({
          value: values.join(','),
          assets
        });
      },

      /**
       * Click-Handler für den Asset-Löschen-Button
       *
       * @param event
       */
      onRemoveAssetClick (event) {
        const assetIdToRemove = $(event.currentTarget)
          .attr('data-asset-id');
        const newAssetIds = this.getAssetIdsArray()
          .filter((assetId) => assetId !== assetIdToRemove);
        this.updateModel(newAssetIds.join(','));
        this.render();
      },

      /**
       * Öffnet den Tessa-Dialog zum Auswählen von Assets
       *
       * @returns {*}
       */
      openModal () {

        $(window)
          .on('message', this.boundReceiveMessage);

        // Modal
        this.modalBox = new Backbone.BootstrapModal({
          modalOptions: {
            backdrop: 'static',
            keyboard: false
          },
          allowCancel: true,
          okCloses: false,
          title: _.__('tessa.asset management.title'),
          content: '',
          cancelText: _.__('tessa.asset management.cancel'),
          okText: _.__('tessa.asset management.confirm'),
          template: this.modalTemplate
        });
        this.modalBox.$el.addClass('EikonModalAssetsSelection');
        this.modalBox.open();

        this.modalBox.$el.find('iframe')
          .on('load', this.onIframeReady.bind(this))
          .prop('src', this.getUrl());


        this.modalBox.on('hidden', () => {
          $(window)
            .off('message', this.boundReceiveMessage);
        });

        // Lademaske
        const loadingMask = new LoadingMask();
        loadingMask.render()
          .$el
          .appendTo(this.modalBox.$el.find('.modal-body'))
          .css({
            'position': 'absolute',
            'width': '100%',
            'height': '100%',
            'top': '0',
            'left': '0'
          });
        loadingMask.show();

        setTimeout(function () {
          loadingMask.hide()
            .$el
            .remove();
        }, 5000);
      },

      /**
       * Wird gerufen, wenn neue Assets im Tessa-Dialog
       * ausgewählt und gespeichert wurden
       *
       * @param event
       */
      receiveMessage (event) {
        const receivedData = JSON.parse(event.originalEvent.data);
        const sids = receivedData.map((value) => value.position_asset_system_id);

        this.updateModel(sids.join(','));
        this.render();

        if (this.modalBox) {
          this.modalBox.close();
        }
      },


      /**
       * Wird gerufen, wenn die iFrame für den Tessa-Dialog
       * bereit ist. Sendet die aktuellen Assets an den Dialog.
       *
       * @param e
       */
      onIframeReady (e) {
        const iframe = e.target;
        const iframeContent = iframe.contentWindow;

        if (!iframe.src) {
          return;
        }

        iframeContent.postMessage(JSON.stringify({
          'selected': this.getAssetIdsArray()
        }), '*');
      },

      /**
       * Erzeugt die URL für den Tessa-Dialog
       *
       * @returns {string}
       */
      getUrl () {
        const entityCode = this.getFormData().code.toUpperCase();
        const entityModule = this.getFormData().meta.customEntityName.toUpperCase();
        const identifier = `RD.${entityModule}.${entityCode}`;

        const data = {
          ProductId: identifier,
          attribute: JSON.stringify({
            code: this.config.fieldName,
            type: 'tessa',
            'allowed_extensions': this.getOption('allowedExtensions'),
            'max_assets': this.getOption('maximumCount')
          }),
          context: JSON.stringify({
            locale: null,
            scope: null,
            data: this.getAssetIdsArray()
          })
        };

        return Routing.generate('eikona_tessa_media_select', {
          data: jQuery.param(data)
        });
      },

      getAssetIdsArray () {
        const assetIdString = this.getModelValue();
        if (typeof assetIdString === 'string' && assetIdString.length) {
          return assetIdString.split(',');
        }

        return [];
      }
    });
  }
);
