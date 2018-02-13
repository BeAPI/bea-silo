/* global bea_silo */

import '../polyfill/forEach'
import { scrollIt, fadeIn } from './utils'

class Silo {
  /**
   * Iterate through targeted elements to init silo
   * @param {string} selector
   * @param {bool} scrollable
   * @return {object} silo
   */
  static bind (selector, opts, scrollable = true) {
    document.querySelectorAll(selector).forEach(el => {
      const preview = el.dataset.preview
      const name = el.dataset.name
      const silo = new Silo(name, opts, el, preview, scrollable)
      return silo
    })
  }

  /**
   * Constructor for Silo class
   * @param {string} name
   * @param {HTMLElement} container
   * @param {bool} preview
   * @param {bool} scrollable
   */
  constructor (name, opts, container, preview = false, scrollable) {
    this.name = name
    this.opts = opts
    this.container = container
    this.preview = preview
    this.terms = bea_silo.objects[this.name].terms
    this.baseSlug = bea_silo.objects[this.name].base_slug
    this.scrollable = scrollable
    this.init()
    this.container.addEventListener('click', this.handleClick.bind(this))
    // Listen for back history
    window.onpopstate = (event) => {
      // Remove all elements from container
      this.container.innerHTML = ''
      this.init()
    }
  }

  /**
   * Display items according to the current URL
  */
  checkUrl () {
    const currentUrl = window.location.href
    const item = this.terms.filter(term => term.term_link === currentUrl)[0]
    const parentIds = []
    // Store all parent id in an array
    for (let i = item.level + 1, j = 0; i >= 0; i--, j++) {
      if (!parentIds.length) {
        const parentId = this.terms.filter(term => term.term_id === item.term_id)[0].term_id
        parentIds.push(parentId)
      } else {
        const parentId = this.terms.filter(term => term.term_id === parentIds[j - 1])[0].parent
        parentIds.push(parentId)
      }
    }
    // Reverse array to display element in right order
    const reverse = parentIds.reverse()
    if (this.scrollable) {
      reverse.forEach((id, index) => {
        if (this.container.querySelector(`.silo__item[data-id="${id}"]`)) {
          this.container.querySelector(`.silo__item[data-id="${id}"]`).classList.add(this.opts.activeClass)
        }
        this.displayLevel(index, id)
      })
    } else {
      // If Silo is not scrollable, display only last element
      this.displayLevel()
      // add active class on level 1 item
      this.container.querySelector(`.silo__item[data-id="${reverse[1]}"]`).classList.add(this.opts.activeClass)
      this.displayLevel(reverse.length - 1, reverse[reverse.length - 1])
    }
  }

  /**
   * Handle click event on silo items
   * @param {event} e
   */
  handleClick (e) {
    const parentNode = e.target.parentNode
    // Follow link if preview mode is on, do nothing if wrong target
    if (this.preview || !parentNode.classList.contains('silo__item')) {
      return
    }
    // If items has childrens prevent the link
    if (parentNode.dataset.childrens.length > 0) {
      e.preventDefault()
    }
    // If item is already active, remove class and the associate row
    if (parentNode.classList.contains(this.opts.activeClass)) {
      const level = parseInt(parentNode.dataset.level)
      this.removePrevRow(parentNode, level)
      this.handlePrevHistory(parentNode)
      return
    }
    const level = parseInt(parentNode.dataset.level)
    const parent = parseInt(parentNode.dataset.id)
    const link = parentNode.dataset.link
    const slug = parentNode.dataset.slug
    this.removePrevRow(parentNode, level)
    this.handleActiveClass(parentNode)
    window.history.pushState(slug, slug, link)
    this.displayLevel(level + 1, parent)
  }

  /**
   * Handle history push state when uncheck an item
   * @param {HTMLElement} el
   */
  handlePrevHistory (el) {
    const term = this.terms.filter(term => term.childrens.length && term.childrens.includes(parseInt(el.dataset.id)))[0]
    if (term) {
      window.history.pushState(term.slug, term.slug, term.term_link)
    } else {
      window.history.pushState(this.baseSlug, this.baseSlug, `${window.location.protocol}//${window.location.host}/${this.baseSlug}`)
    }
    if (this.scrollable) {
      scrollIt(el.parentNode, 50, 300)
    }
  }

  /**
   * Handle active class on silo items
   * @param {HTMLElement} parent
   */
  handleActiveClass (parent) {
    const row = parent.parentNode
    const items = row.querySelectorAll('.silo__item')
    items.forEach(item => item.classList.remove(this.opts.activeClass))
    parent.classList.add(this.opts.activeClass)
  }

  /**
   * Remove row when an item is unchecked
   * @param {HTMLElement} el
   * @param {int} level
   */
  removePrevRow (el, level = 0) {
    const rows = document.querySelectorAll('.silo__row')
    rows.forEach(row => {
      const rowLevel = row.dataset.level
      if (rowLevel > level) {
        row.parentNode.removeChild(row)
      }
    })
    el.classList.remove(this.opts.activeClass)
  }

  /**
   * Create row container for silo items
   * @param {int} level
   */
  createRow (level = 0) {
    const row = document.createElement('DIV')
    row.classList.add(`silo__row`, `silo__row--${level}`)
    row.setAttribute('data-level', level)
    return row
  }

  /**
   * Display all silo items of the current level
   * @param {int} level
   * @param {HTMLElement} parent
   */
  displayLevel (level = 0, parent = 0) {
    // Get items according to current level and parent
    const items = this.terms.filter(term => term.level === level && term.parent === parent)
    // Create row to append items
    const row = this.createRow(level)
    // Store prev row if we need to remove it
    const prevRow = this.container.querySelector(`.silo__row--${level - 1}`)
    // Create each div for silo item
    items.forEach(item => {
      const div = document.createElement('DIV')
      div.setAttribute('data-level', level)
      div.setAttribute('data-childrens', item.childrens)
      div.setAttribute('data-id', item.term_id)
      div.setAttribute('data-slug', item.slug)
      div.setAttribute('data-link', item.term_link)
      div.classList.add(`silo__item`, `silo__item--${level}`)
      div.innerHTML = `<a href="${item.term_link}">
                        <span class="item__title">
                          ${item.name}
                        </span>
                      </a>`
      row.appendChild(div)
    })
    // if silo not scrollable remove prev row
    if (!this.scrollable && level > 1 && prevRow) {
      prevRow.parentNode.removeChild(prevRow)
    }
    // append row in dom
    this.container.appendChild(row)
    if (level !== 0) {
      if (this.opts.animation === 'fadeIn') {
        fadeIn(row)
      }
    }
    // if silo is scrollable, smooth scroll to the row
    if (this.scrollable) {
      scrollIt(row, 50, 300)
    }
  }

  /**
   * Init Silo
  */
  init () {
    if (window.location.href !== `${window.location.protocol}//${window.location.host}/${this.baseSlug}` &&
        window.location.href !== `${window.location.protocol}//${window.location.host}/${this.baseSlug}/`) {
      this.checkUrl()
    } else {
      this.displayLevel()
    }
  }
}

export default Silo