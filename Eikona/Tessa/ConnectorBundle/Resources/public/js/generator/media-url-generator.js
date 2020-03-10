'use strict';

define(
  [
    'jquery',
    'underscore',
    'routing'
  ], function (
    $,
    _,
    Routing
  ) {
    return {
      tessaPrefix: '%tessa%_',

      /**
       * Get the show media URL
       *
       * @param {string} filePath
       * @param {string} filter
       *
       * @return {string}
       */
      getMediaShowUrl (filePath, filter) {

        if (this.isTessaImage(filePath)) {
          const assetId = filePath.substr(this.tessaPrefix.length);
          return Routing.generate('eikona_tessa_media_preview', {assetId});
        }

        const filename = encodeURIComponent(filePath);

        return Routing.generate('pim_enrich_media_show', {
          filename,
          filter
        });
      },

      /**
       * Get the download media URL
       *
       * @param {string} filePath
       *
       * @return {string}
       */
      getMediaDownloadUrl (filePath) {

        if (this.isTessaImage(filePath)) {
          const assetId = filePath.substr(this.tessaPrefix.length);
          return Routing.generate('eikona_tessa_media_detail', {assetId});
        }

        const filename = encodeURIComponent(filePath);

        return Routing.generate('pim_enrich_media_download', {
          filename
        });
      },

      isTessaImage (filePath) {
        return typeof filePath === 'string' && filePath.startsWith(this.tessaPrefix);
      }
    };
  }
);
