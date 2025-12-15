class Task {
	constructor(id, title, details, summary, column, dueDate, createdDate, priority, assignee, tags, status, project, comments) {
		this.id = id
		this.title = title
		this.details = details
		this.summary = summary
		this.column = column
		this.dueDate = dueDate
		this.createdDate = createdDate
		this.priority = priority
		this.assignee = assignee
		this.tags = tags
		this.status = status
		this.project = project
		this.comments = comments
	}
}

class TaskManager {
	constructor() {
		this.tasks = []
	}

	addTask(task) {
		this.tasks.push(task)
	}

	removeTask(taskId) {
		this.tasks = this.tasks.filter((task) => task.id !== taskId)
	}

	updateTask(taskId, updatedProperties) {
		const taskIndex = this.tasks.findIndex((task) => task.id === taskId)
		if (taskIndex !== -1) {
			Object.assign(this.tasks[taskIndex], updatedProperties)
		}
	}

	getTaskById(taskId) {
		return this.tasks.find((task) => task.id === taskId)
	}
}

function kanban(demoTasks) {

	const taskManager = new TaskManager()
	let draggedElement = null
	const allowDrop = (event) => event.preventDefault()
	const drag = (event) => (draggedElement = event.target)

	const drop = (event) => {
		event.preventDefault()
		if (!draggedElement) return

		const targetColumn = event.target.closest(".col")
		if (!targetColumn) return

		const mouseY = event.clientY
		const columnCards = Array.from(targetColumn.getElementsByClassName("card"))
		let targetCard = null

		for (const card of columnCards) {
			const cardRect = card.getBoundingClientRect()
			if (mouseY < cardRect.top + cardRect.height / 2) {
				targetCard = card
				break
			}
		}

		!targetCard ? targetColumn.appendChild(draggedElement) : targetColumn.insertBefore(draggedElement, targetCard)

		const taskId = parseInt(draggedElement.id.split("-")[1])
		const task = taskManager.getTaskById(taskId)
		task.column = targetColumn.id

		draggedElement = null
		updateTaskCounts()
		showToast(`Task ${getTaskShortcut(task)} updated to ${task.column.split("-")[0]}.`, "success")
	}

	const createTaskCard = (task) => {
		const taskCard = document.createElement("div")
		taskCard.classList.add("card", "shadow-sm")
		taskCard.draggable = true
		taskCard.id = `task-${task.id}`
		taskCard.addEventListener("dragstart", drag)

		const [firstName, lastName] = task.assignee.name.split(" ")

		taskCard.innerHTML = `
    <div class="card-header p-2 bg-white border-0">
      <span class="badge bg-light text-dark">${getTaskShortcut(task)}</span>
      <span class="badge bg-light text-dark">${task.priority.level}</span>
      <div class="dropdown float-end dropstart">
        <button class="btn btn-link p-0" aria-label="Task Action Options" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa fa-ellipsis-vertical"></i> 
        </button>
        <ul class="dropdown-menu">
          <li><a href="#" class="dropdown-item mb-0" onclick="editTask(${task.id})">Edit</a></li>
          <li><a href="#" class="dropdown-item mb-0" onclick="#">Duplicate</a></li>
          <li><a href="#" class="dropdown-item mb-0" onclick="deleteTask(${task.id})">Delete</a></li>
        </ul>
      </div>
    </div>
    <div class="card-body px-2 py-0">
      <h2 class="h6 fw-medium text-primary text-start btn p-0 m-0" onclick="showTaskDetails(${task.id})">${task.title}</h2>
      <p class="m-0 opacity-75 snippet">${task.summary}</p>
      <p class="m-0 mt-2 d-none opacity-75 small"><i class='fa-regular fa-calendar fa-sm me-1'></i> ${task.dueDate}</p>
    </div>
    <div class="card-footer p-2 bg-white border-0 small pt-3">
      <span class="badge assignee-cover border text-dark" title="${task.assignee}">${firstName?.charAt(0).toUpperCase()}${lastName?.charAt(0).toUpperCase() || "-"}</span>
      ${task.comments.length !== 0 ? `<a href="" class="btn btn-link btn-xxs border float-end"><i class="fa fa-comment"></i> ${task.comments.length}</a>` : ""}
      </div>
  `
		return taskCard
	}

	const searchTasks = (searchTerm) => {
		if (searchTerm === " ") {
			renderTasks(taskManager.tasks)
		} else {
			const filteredTasks = taskManager.tasks.filter((task) => task.title.toLowerCase().includes(searchTerm.toLowerCase()) || task.details.toLowerCase().includes(searchTerm.toLowerCase()) || task.assignee.name.toLowerCase().includes(searchTerm.toLowerCase()))
			renderTasks(filteredTasks)
		}
	}

	const colorPalette = [
		"#33FF57", // Green
		"#F1C40F", // Yellow
		"#7F669D", // Purple
		"#6D8B74", // Teal
		"#BB6464", // Coral
		"#ECA869", // Orange
	]

	const colorObject = {}

	const assignColorToString = (str) => {
		if (colorObject.hasOwnProperty(str)) return colorObject[str]
		if (colorPalette.length === 0) return

		const color = colorPalette.pop()
		colorObject[str] = color

		return colorObject[str]
	}

	const updateTaskCounts = () => {
		const columns = ["todo-column", "inprogress-column", "review-column", "done-column"]
		const totalTasks = taskManager.tasks.length
		const doneTasks = taskManager.tasks.filter((task) => task.column === "done-column").length

		columns.forEach((columnId) => {
			const column = document.getElementById(columnId)
			const taskCards = column.getElementsByClassName("card")
			const taskCount = taskCards.length

			const countElement = column.querySelector(`h2 span`)
			if (countElement) {
				countElement.textContent = taskCount
			}
		})
		const progress = totalTasks === 0 ? 0 : Math.round((doneTasks / totalTasks) * 100)

		const progressElement = document.getElementById("progress-indicator")
		const progressLabel = document.getElementById("progress-label")
		if (progressElement) {
			progressLabel.textContent = `${progress}% Complete`
			progressElement.style.width = `${progress}%`
		}
	}

	const editTask = (taskId) => {
		const task = taskManager.getTaskById(taskId)

		if (!task) return console.error("Task not found")

		const taskForm = document.getElementById("taskForm")
		const taskDetailsView = document.getElementById("taskDetailsView")
		document.querySelector("#category-wrap").classList.remove("d-none")
		taskForm.classList.remove("d-none")
		taskDetailsView.classList.add("d-none")

		resetTaskForm()

		document.getElementById("task-title").value = task.title
		document.getElementById("task-details").value = task.details
		document.getElementById("task-summary").value = task.summary
		document.getElementById("task-priority").value = task.priority.value || ""
		document.getElementById("task-due-date").value = new Date(task.dueDate).toISOString().split("T")[0] || ""
		document.getElementById("task-tags").value = task.tags ? task.tags.join(", ") : ""
		document.getElementById("task-category").value = task.column

		const assignees = [...new Set(taskManager.tasks.map((task) => task.assignee))]
		const assigneeDropdown = document.getElementById("task-assignee")
		assigneeDropdown.innerHTML = ""

		assignees.forEach(({ id, name }) => {
			const option = document.createElement("option")
			option.value = id
			option.textContent = name
			assigneeDropdown.appendChild(option)
		})

		assigneeDropdown.value = task.assignee.id || ""

		document.getElementById("taskModalLabel").textContent = "Edit Task"
		const saveButton = document.getElementById("save-task-btn")
		saveButton.setAttribute("data-task-id", task.id)

		const modal = new bootstrap.Modal(document.getElementById("taskModal"))
		modal.show()
	}

	const showTaskDetails = (taskId) => {
		const task = taskManager.getTaskById(taskId)
		if (!task) return console.error("Task not found")

		const taskForm = document.getElementById("taskForm")
		const taskDetailsView = document.getElementById("taskDetailsView")
		taskForm.classList.add("d-none")
		taskDetailsView.classList.remove("d-none")

		document.getElementById("taskModalLabel").innerHTML = `${getTaskShortcut(task)} <a href='#'><i class="fa fa-share-nodes fa-sm ms-1"></i></a>`
		document.getElementById("task-view-title").textContent = task.title
		document.getElementById("task-view-details").textContent = task.details
		document.getElementById("task-view-priority").textContent = task.priority.level
		document.getElementById("task-view-due-date").textContent = formatDate(task.dueDate)
		document.getElementById("task-view-status").textContent = task.status
		document.getElementById("task-tags").value = task.tags.join(", ")
		const [firstName, lastName] = task.assignee.name.split(" ")
		document.getElementById("task-view-assignee").innerHTML = `<span class="badge assignee-cover border text-dark" title="${task.assignee.name}">${firstName?.charAt(0).toUpperCase()}${lastName?.charAt(0).toUpperCase() || "-"}</span> ${task.assignee.name}`
		document.getElementById("task-category").value = task.column

		const modal = new bootstrap.Modal(document.getElementById("taskModal"))
		modal.show()
	}

	const saveTask = () => {
		let taskId = document.getElementById("save-task-btn").getAttribute("data-task-id")
		const title = document.getElementById("task-title").value
		const column = document.getElementById("task-category").value
		const prioritySelect = document.getElementById("task-priority")
		const selectedOption = prioritySelect.options[prioritySelect.selectedIndex]
		const priority = { level: selectedOption.text, value: selectedOption.value } || ""
		const dueDate = document.getElementById("task-due-date").value || ""
		const tags = document
			.getElementById("task-tags")
			.value.split(",")
			.map((tag) => tag.trim())
		const assignee = document.getElementById("task-assignee").value || ""
		const summary = document.getElementById("task-summary").value || ""
		const details = document.getElementById("task-details").value || ""
		const project = { id: 1, name: "Operation Zenith Sunrise", startDate: "2024-01-01", endDate: "2025-12-31" }
		const status = column
		const comments = []

		if (taskId === "new") {
			const createdDate = new Date()
			const newTask = new Task(
				taskManager.tasks.length + 1,
				title,
				details,
				summary,
				column,
				dueDate,
				createdDate,
				priority,
				taskManager.tasks.find((task) => task.assignee.id === parseInt(assignee)).assignee,
				tags,
				status,
				project,
				comments,
			)

			taskManager.addTask(newTask)

			const columnElement = document.getElementById(column)
			const newTaskCard = createTaskCard(newTask)

			columnElement.appendChild(newTaskCard)

			showToast("Task added!", "success")
		} else {
			taskId = parseInt(taskId)

			const updatedProperties = {
				id: taskId,
				title,
				details,
				summary,
				column,
				dueDate,
				priority,
				assignee: taskManager.tasks.find((task) => task.assignee.id === parseInt(assignee)).assignee,
				tags,
			}
			taskManager.updateTask(taskId, updatedProperties)

			const taskCard = document.getElementById(`task-${taskId}`)
			const updatedTaskCard = createTaskCard(taskManager.getTaskById(taskId))

			const currentColumn = taskCard.closest(".col")
			const newColumn = document.getElementById(updatedProperties.column)

			if (currentColumn !== newColumn) {
				currentColumn.removeChild(taskCard)
				newColumn.appendChild(updatedTaskCard)
			} else {
				currentColumn.replaceChild(updatedTaskCard, taskCard)
			}
			showToast("Task updated!", "info")
		}

		updateTaskCounts()

		const modal = bootstrap.Modal.getInstance(document.getElementById("taskModal"))
		modal.hide()
	}

	const deleteTask = (taskId) => {
		taskManager.removeTask(taskId)
		const taskCard = document.getElementById(`task-${taskId}`)
		taskCard.remove()
		updateTaskCounts()
		showToast("Task deleted!", "success")
	}

	const resetTaskForm = () => {
		document.getElementById("task-title").value = ""
		document.getElementById("task-details").value = ""
		document.getElementById("task-summary").value = ""
		document.getElementById("task-priority").value = ""
		document.getElementById("task-due-date").value = ""
		document.getElementById("task-tags").value = ""
		document.getElementById("task-assignee").value = ""
		document.getElementById("task-category").value = ""
	}

	const setTaskCategory = (columnId) => {
		resetTaskForm()

		const taskForm = document.getElementById("taskForm")
		const taskDetailsView = document.getElementById("taskDetailsView")
		const assignees = [...new Set(taskManager.tasks.map((task) => task.assignee))]
		const assigneeDropdown = document.getElementById("task-assignee")
		assigneeDropdown.innerHTML = ""

		assignees.forEach((assignee) => {
			const option = document.createElement("option")
			option.value = assignee.id
			option.textContent = assignee.name
			assigneeDropdown.appendChild(option)
		})
		document.querySelector("#category-wrap").classList.add("d-none")
		taskForm.classList.remove("d-none")
		taskDetailsView.classList.add("d-none")

		document.getElementById("task-category").value = columnId

		document.getElementById("taskModalLabel").textContent = "Add New Task"
		const saveButton = document.getElementById("save-task-btn")
		saveButton.setAttribute("data-task-id", "new")
	}

	const getTaskShortcut = (task) => {
		const projectName = task.project.name
		if (projectName.split(" ").length === 1) {
			return projectName.toUpperCase().substring(0, 3) + "-" + task.id
		} else {
			return (
				projectName
					.split(" ")
					.map((word) => word[0])
					.join("") +
				"-" +
				task.id
			)
		}
	}

	const showToast = (message, type = "success") => {
		const toastContainer = document.querySelector(".toast-container")
		const toast = document.createElement("div")
		toast.classList.add("toast", "text-bg-" + type, "border-0")
		toast.setAttribute("role", "alert")
		toast.setAttribute("aria-live", "assertive")
		toast.setAttribute("aria-atomic", "true")

		toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 mt-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `

		toastContainer.appendChild(toast)

		const bsToast = new bootstrap.Toast(toast)
		bsToast.show()

		setTimeout(() => {
			toastContainer.removeChild(toast)
		}, 500000) // 5 seconds
	}

	const handleSidebar = () => {
		const sidebar = document.querySelector("#sidebar")
		sidebar.classList.toggle("d-block")
	}

	const formatDate = (dateString, options = { year: "numeric", month: "long", day: "numeric" }, locale = "en-US") => {
		const date = new Date(dateString)
		return new Intl.DateTimeFormat(locale, options).format(date)
	}

	const renderTasks = (tasks) => {
		const columns = ["todo-column", "inprogress-column", "review-column", "done-column"]

		columns.forEach((columnId) => {
			const column = document.getElementById(columnId)
			const cards = column.querySelectorAll(".card")
			cards.forEach((card) => card.remove())
		})

		tasks.forEach((task) => {
			const column = document.getElementById(task.column)
			const taskCard = createTaskCard(task)
			column.appendChild(taskCard)
		})

		updateTaskCounts()
	}


	demoTasks.forEach(({ id, title, details, summary, column, dueDate, createdDate, priority, assignee, tags, status, project, comments }) =>
		taskManager.addTask(new Task(id, title, details, summary, column, dueDate, createdDate, priority, assignee, tags, status, project, comments))
	)

	renderTasks(taskManager.tasks)
}