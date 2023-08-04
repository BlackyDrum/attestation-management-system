export default function reduce_tasks(tasks, userData, headers) {
    const uniqueTitles = Array.from(new Set(tasks.flat().map((item) => item.title)));

    const usersData = {};

    tasks.flat().forEach((item) => {
        const {name, title, task_id, checked, user_id, editor_name, updated_at, matriculation_number} = item;
        const key = `${name}-${user_id}`;

        if (!usersData[key]) {
            usersData[key] = {Name: name, user_id};
            uniqueTitles.forEach((t) => {
                usersData[key][t] = false;
            });
        }

        usersData[key][title] = checked;
        usersData[key]['matriculation_number'] = matriculation_number;
        usersData[key][`task_id_${title}`] = task_id;
        usersData[key][`editor_name_${title}`] = editor_name;
        usersData[key][`updated_at_${title}`] = updated_at;
    });

    userData = Object.values(usersData);
    userData = userData.slice().sort((a, b) => {
        const surnameA = a.Name.split(' ').slice(-1)[0];
        const surnameB = b.Name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });

    headers = Object.keys(userData[0]).filter((key) => key !== 'Name' && key !== 'matriculation_number' && key !== 'user_id' && key !== 'editor_id' && !key.startsWith('updated_at') && !key.startsWith('editor_name') && !key.startsWith('task_id'));

    let sortable = [];
    for (const header of headers) {
        sortable.push([header, userData[0][`task_id_${header}`]]);
    }
    sortable.sort((a, b) => {
        return a[1] - b[1];
    });
    headers = sortable.flat().filter(item => headers.includes(item));

    return {
        tasks: tasks,
        userData: userData,
        headers: headers,
    }
}
