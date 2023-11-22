export default function checkPrivilege(wantedPrivilege, userPrivilege) {
    for (const p of userPrivilege) {
        if (p.privilege === wantedPrivilege && p.checked) {
            return true;
        }
    }
    return false;
}
