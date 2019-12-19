export function getUrlQueryParams(url) {
  let params = {};
  if (url.includes("?")) {
    let queryParams = url.split("?")[1].trim("&").split("&");
    for (let i = 0; i < queryParams.length; i++) {
      let [key, val] = queryParams[i].split("=");
      params[key] = val;
    }
  }
  return params;
}

export function addUrlQueryParam(key, val) {
  const params = getUrlQueryParams(window.location.search);
  params[key] = val;

  const paramKeys = Object.keys(params);
  let queryParams = [];

  for (let i = 0; i < paramKeys.length; i++) {
    let k = paramKeys[i];
    let v = params[k];
    if (v.toString() !== '') {
      queryParams.push(`${k}=${v}`);
    }
  }

  return queryParams.length > 0 ? `?${queryParams.join("&")}` : "";
}