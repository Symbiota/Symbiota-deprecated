/**
 * @param url URL to GET
 * @returns {Promise<string>} Either the response text or error code/text
 */
function httpGet(url) {
  return new Promise((resolve, reject) => {
    const req = new XMLHttpRequest();
    req.onload = () => {
      if (req.status === 200) {
        resolve(req.responseText);
      } else {
        reject(`${req.status.toString()} ${req.statusText}`);
      }
    };

    req.open("GET", url);
    req.send();
  });
}

export default httpGet;