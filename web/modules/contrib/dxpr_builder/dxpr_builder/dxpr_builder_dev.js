/**
 * @file
 * Provides functionality for development team.
 */

(function() {
  const waitForElelement = (selector) => {
    return new Promise(resolve => {
      if (document.querySelector(selector)) {
        return resolve(document.querySelector(selector));
      }

      const observer = new MutationObserver(mutations => {
        if (document.querySelector(selector)) {
          resolve(document.querySelector(selector));
          observer.disconnect();
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    });
  };

  const urlExists = (url, linkElement) => {
    const dxprValidator = '/dxpr_builder/ajax/help_link_validator';

    return fetch(dxprValidator, {
      method: 'POST',
      headers: {
        'Accept': 'application/json, text/plain, */*',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `help_link=${url}`,
    })
      .then(response => response.json())
      .then(data => {
        return [
          data[0],
          linkElement
        ]
      });
  };

  waitForElelement('.dxpr-builder-settings-modal')
    .then((modal) => modal.querySelector('.help-link'))
    .then((helpElement) => {
      const helpLink = helpElement.getAttribute('href');

      urlExists(helpLink, helpElement).then(result => {
        let validLink, helpElement;
        [validLink, helpElement] = result;

        if (validLink !== true) {
          helpElement.classList.add('missing-link');
        }
      });
    });
})();
