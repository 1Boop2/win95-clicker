// Call this before rendering upgrades list.
function sortUpgradesAscending(items){
  return [...items].sort((a,b)=>{
    const ca = (a.next_cost ?? a.cost ?? Number.MAX_SAFE_INTEGER);
    const cb = (b.next_cost ?? b.cost ?? Number.MAX_SAFE_INTEGER);
    return ca - cb || String(a.code||'').localeCompare(String(b.code||''));
  });
}
// Example usage:
// const items = sortUpgradesAscending(state.shop);
// items.forEach(renderUpgradeItem);
