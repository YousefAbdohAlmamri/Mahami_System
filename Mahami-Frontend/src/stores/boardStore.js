import { defineStore } from 'pinia'
import { ref } from 'vue'
import boardService from '@/services/boardService'
import listService from '@/services/listService'
import taskService from '@/services/taskService'

export const useBoardStore = defineStore('board', () => {
  const boards = ref([])
  const currentBoard = ref(null)
  const isLoading = ref(false)
  const error = ref(null)

  // 1. Board Operations
  async function fetchBoards(workspaceId = null) {
    isLoading.value = true
    error.value = null
    try {
      const params = {}
      if (workspaceId) {
        params.workspace_id = workspaceId
      }
      const response = await boardService.getBoards(params)
      boards.value = response.data || []
      return boards.value
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchBoard(id) {
    isLoading.value = true
    error.value = null
    try {
      const response = await boardService.getBoard(id)
      currentBoard.value = response.data
      return currentBoard.value
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function createBoard(data) {
    const response = await boardService.createBoard(data)
    const newBoard = response.data
    boards.value.unshift(newBoard)
    return newBoard
  }

  async function updateBoard(id, data) {
    const response = await boardService.updateBoard(id, data)
    const updated = response.data

    const index = boards.value.findIndex((b) => String(b.id) === String(id))
    if (index !== -1) {
      boards.value[index] = { ...boards.value[index], ...updated }
    }

    if (currentBoard.value && String(currentBoard.value.id) === String(id)) {
      currentBoard.value = { ...currentBoard.value, ...updated }
    }

    return updated
  }

  async function deleteBoard(id) {
    await boardService.deleteBoard(id)
    boards.value = boards.value.filter((b) => String(b.id) !== String(id))
    if (currentBoard.value && String(currentBoard.value.id) === String(id)) {
      currentBoard.value = null
    }
  }

  // 2. Column / List Operations
  async function createList(boardId, data) {
    const response = await listService.createList(boardId, data)
    const newList = { ...response.data, tasks: [] }

    if (currentBoard.value && String(currentBoard.value.id) === String(boardId)) {
      if (!currentBoard.value.lists) currentBoard.value.lists = []
      currentBoard.value.lists.push(newList)
    }

    return newList
  }

  async function updateList(listId, data) {
    const response = await listService.updateList(listId, data)
    const updated = response.data

    if (currentBoard.value?.lists) {
      const list = currentBoard.value.lists.find((l) => String(l.id) === String(listId))
      if (list) {
        list.title = updated.title
      }
    }

    return updated
  }

  async function deleteList(listId) {
    await listService.deleteList(listId)

    if (currentBoard.value?.lists) {
      currentBoard.value.lists = currentBoard.value.lists.filter((l) => String(l.id) !== String(listId))
    }
  }

  async function reorderLists(boardId, orderedIds) {
    const response = await listService.reorderLists(boardId, orderedIds)
    const reordered = response.data

    if (currentBoard.value && String(currentBoard.value.id) === String(boardId)) {
      // Re-map tasks preserving existing state
      const taskMap = new Map()
      currentBoard.value.lists.forEach((l) => {
        taskMap.set(l.id, l.tasks || [])
      })

      currentBoard.value.lists = reordered.map((col) => ({
        ...col,
        tasks: taskMap.get(col.id) || [],
      }))
    }

    return reordered
  }

  // 3. Task / Card Operations
  async function createTask(listId, data) {
    const response = await taskService.createTask(listId, data)
    const newTask = response.data

    if (currentBoard.value?.lists) {
      const list = currentBoard.value.lists.find((l) => String(l.id) === String(listId))
      if (list) {
        if (!list.tasks) list.tasks = []
        list.tasks.push(newTask)
      }
    }

    return newTask
  }

  async function updateTask(taskId, data) {
    const response = await taskService.updateTask(taskId, data)
    const updated = response.data

    if (currentBoard.value?.lists) {
      for (const list of currentBoard.value.lists) {
        const taskIndex = list.tasks?.findIndex((t) => String(t.id) === String(taskId)) ?? -1
        if (taskIndex !== -1) {
          list.tasks[taskIndex] = { ...list.tasks[taskIndex], ...updated }
          break
        }
      }
    }

    return updated
  }

  async function deleteTask(taskId) {
    await taskService.deleteTask(taskId)

    if (currentBoard.value?.lists) {
      for (const list of currentBoard.value.lists) {
        const taskIndex = list.tasks?.findIndex((t) => String(t.id) === String(taskId)) ?? -1
        if (taskIndex !== -1) {
          list.tasks.splice(taskIndex, 1)
          // Re-index remaining positions
          list.tasks.forEach((t, i) => {
            t.position = i
          })
          break
        }
      }
    }
  }

  async function moveTask(taskId, targetListId, position) {
    const response = await taskService.moveTask(taskId, targetListId, position)
    const moved = response.data

    // Refresh full board or synchronize local state
    if (currentBoard.value) {
      await fetchBoard(currentBoard.value.id)
    }

    return moved
  }

  async function reorderTasks(listId, orderedIds) {
    const response = await taskService.reorderTasks(listId, orderedIds)
    const reordered = response.data

    if (currentBoard.value?.lists) {
      const list = currentBoard.value.lists.find((l) => String(l.id) === String(listId))
      if (list) {
        list.tasks = reordered
      }
    }

    return reordered
  }

  return {
    boards,
    currentBoard,
    isLoading,
    error,
    fetchBoards,
    fetchBoard,
    createBoard,
    updateBoard,
    deleteBoard,
    createList,
    updateList,
    deleteList,
    reorderLists,
    createTask,
    updateTask,
    deleteTask,
    moveTask,
    reorderTasks,
  }
})

