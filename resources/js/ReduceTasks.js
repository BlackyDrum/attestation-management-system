// This function is quite complex, but it essentially organizes and rearranges the input
// data to provide a more structured and sorted output.
export default function reduce_tasks(tasks, userData, headers) {
    // Create an array of unique task titles from the 'tasks' data
    const uniqueTitles = Array.from(new Set(tasks.flat().map((item) => item.title)));

    const usersData = {};

    tasks.flat().forEach((item) => {
        // Extract relevant properties from the 'item'
        const {name, title, task_id, checked, user_id, editor_name, updated_at, matriculation_number, comment} = item;
        //console.log(item)
        // Create a unique key based on 'name' and 'user_id'
        const key = `${name}-${user_id}`;

        // If the user data for the key doesn't exist, initialize it
        if (!usersData[key]) {
            usersData[key] = {Name: name, user_id};
            // For each unique title, set the value to 'false' for the user
            uniqueTitles.forEach((t) => {
                usersData[key][t] = false;
            });
        }

        usersData[key][title] = checked;
        usersData[key]['matriculation_number'] = matriculation_number;
        usersData[key][`task_id_${title}`] = task_id;
        usersData[key][`editor_name_${title}`] = editor_name;
        usersData[key][`updated_at_${title}`] = updated_at;
        usersData[key][`comment_${title}`] = comment;
    });

    userData = Object.values(usersData);

    // Sort the userData array based on the last name (extracted from the 'Name' property)
    userData = userData.slice().sort((a, b) => {
        const surnameA = a.Name.split(' ').slice(-1)[0];
        const surnameB = b.Name.split(' ').slice(-1)[0];
        return surnameA.localeCompare(surnameB);
    });

    // Filter and reorder the 'headers' array based on specific criteria
    headers = Object.keys(userData[0]).filter((key) => key !== 'Name' && key !== 'matriculation_number' && key !== 'user_id' && key !== 'editor_id' && !key.startsWith('comment') && !key.startsWith('updated_at') && !key.startsWith('editor_name') && !key.startsWith('task_id'));

    // Create a sortable array from the filtered headers and sort it based on task_ids
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
