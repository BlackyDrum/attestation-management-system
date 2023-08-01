export default function combine(data) {
    const combinedData = data.reduce((acc, item) => {
        const foundItem = acc.find(entry => (
            entry.id === item.id &&
            entry.subject_name === item.subject_name &&
            entry.subject_number === item.subject_number &&
            entry.creator_id === item.creator_id &&
            entry.semester === item.semester
        ));

        const task = {
            task_id: item.task_id,
            title: item.title,
            description: item.description,
            user_id: item.user_id,
            name: item.name,
            checked: item.checked,
            editor_id: item.editor_id,
            editor_name: item.editor_name,
            updated_at: item.updated_at
        };

        if (foundItem) {
            const existingTaskIndex = foundItem.tasks.findIndex(f => (
                f.title === task.title &&
                f.description === task.description &&
                f.user_id === task.user_id &&
                f.name === task.name && f.checked === task.checked &&
                f.editor_id === task.editor_id && f.editor_name === task.editor_name &&
                f.updated_at === task.updated_at
            ));

            if (existingTaskIndex === -1) {
                foundItem.tasks.push(task);
            }
        } else {
            acc.push({
                id: item.id,
                subject_name: item.subject_name,
                subject_number: item.subject_number,
                creator_id: item.creator_id,
                semester: item.semester,
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

    return combinedData;
}
