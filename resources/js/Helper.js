export function getUsersWithMatriculationNumbers(users) {
    let userWithMatriculationNumber = JSON.parse(JSON.stringify(users));
    userWithMatriculationNumber = userWithMatriculationNumber.slice().sort((a, b) => {
        const surnameA = a.name.split(' ').slice(-1)[0];
        const surnameB = b.name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });
    userWithMatriculationNumber.map(user => {
        user.name = `${user.name} (${user.matriculation_number})`;
    })

    return userWithMatriculationNumber;
}
