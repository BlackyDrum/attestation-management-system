// This function combines the data based on specific properties coming from the controller,
// groups tasks within each entry by user_id, and finally sorts the
// combined data array alphabetically by subject_name
export default function combine(data) {
    const combinedData = data.reduce((acc, item) => {
        // Check if an entry with the same properties already exists in the 'acc' array
        const foundItem = acc.find(entry => (
            entry.id === item.id &&
            entry.subject_name === item.subject_name &&
            entry.subject_number === item.subject_number &&
            entry.creator_id === item.creator_id &&
            entry.semester === item.semester &&
            entry.semester_id === item.semester_id &&
            entry.acronym === item.acronym
        ));

        // Extract relevant task properties to simplify comparison
        const task = {
            task_id: item.task_id,
            title: item.title,
            description: item.description,
            user_id: item.user_id,
            name: item.name,
            matriculation_number: item.matriculation_number,
            checked: item.checked,
            editor_id: item.editor_id,
            editor_name: item.editor_name,
            updated_at: item.updated_at,
            comment: item.comment,
        };

        // If an entry with the same properties exists, check if the task is already present
        if (foundItem) {
            const existingTaskIndex = foundItem.tasks.findIndex(f => (
                f.title === task.title &&
                f.description === task.description &&
                f.user_id === task.user_id &&
                f.name === task.name && f.checked === task.checked &&
                f.editor_id === task.editor_id && f.editor_name === task.editor_name &&
                f.updated_at === task.updated_at && f.matriculation_number === task.matriculation_number
            ));

            // If the task doesn't exist, add it to the existing entry's 'tasks' array.
            if (existingTaskIndex === -1) {
                foundItem.tasks.push(task);
            }
        } else {
            // If no matching entry exists, create a new entry in the 'combinedData' array
            acc.push({
                id: item.id,
                acronym: item.acronym,
                subject_name: item.subject_name,
                subject_number: item.subject_number,
                creator_id: item.creator_id,
                semester: item.semester,
                semester_id: item.semester_id,
                tasks: [task],
            });
        }

        return acc;
    }, []);

    // Group the 'tasks' array by 'user_id' within each item of 'combinedData'
    combinedData.forEach(item => {
        const tasksGroupedByUserId = item.tasks.reduce((groups, task) => {
            if (!groups[task.user_id]) {
                groups[task.user_id] = [];
            }
            groups[task.user_id].push(task);
            return groups;
        }, {});
        item.tasks = Object.values(tasksGroupedByUserId);
    });

    // Sort the 'combinedData' array by 'subject_name'
    combinedData.sort((a, b) => {
        const subjectNameA = a.subject_name.toLowerCase();
        const subjectNameB = b.subject_name.toLowerCase();

        return subjectNameA < subjectNameB ? -1 : 1;
    });

    combinedData.map((item, index) => item.index = index);

    return combinedData;
}
