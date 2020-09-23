export function getTaxaPage(clientRoot, tid) {
  return `${clientRoot}/taxa/index.php?taxon=${tid}`;
}

export function getGardenTaxaPage(clientRoot, tid) {
  return `${clientRoot}/taxa/garden.php?taxon=${tid}`;
}

export function getGardenPage(clientRoot, clid) {
  return `${clientRoot}/garden/index.php?clid=${clid}`;

}

export function getImageDetailPage(clientRoot, occid) {
  return `${clientRoot}/collections/individual/index.php?occid=${occid}`;
}

export function getCommonNameStr(item) {
//console.log(item);
  const basename = item.vernacular.basename;
  const names = item.vernacular.names;

  let cname = basename;
  if (names.length > 0) {
    cname = names[0];
  }

  if (cname.includes(basename) && basename !== cname) {
    return `${basename}, ${cname.replace(basename, '')}`
  }

  return cname;
}

export function getChecklistPage(clientRoot,clid,pid) {
  return `${clientRoot}/checklists/checklist.php?cl=${clid}&pid=${pid}`;
}

export function getIdentifyPage(clientRoot,clid,pid) {
  return `${clientRoot}/ident/key.php?cl=${clid}&proj=${pid}&taxon=All+Species`;
}