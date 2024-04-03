import ConfirmMessageModal from '../components/modals/ConfirmMessageModal'
import DQFModal from '../components/modals/DQFModal'
import SplitJobModal from '../components/modals/SplitJob'
import CreateTeamModal from '../components/modals/CreateTeam'
import ModifyTeamModal from '../components/modals/ModifyTeam'
import {mergeJobChunks} from '../api/mergeJobChunks'
import AppDispatcher from '../stores/AppDispatcher'
import ModalsConstants from '../constants/ModalsConstants'

let ModalsActions = {
  showModalComponent: (component, props, title, style, onCloseCallback) => {
    AppDispatcher.dispatch({
      actionType: ModalsConstants.SHOW_MODAL,
      component,
      props,
      title,
      style,
      onCloseCallback,
    })
  },
  onCloseModal: function () {
    AppDispatcher.dispatch({
      actionType: ModalsConstants.CLOSE_MODAL,
    })
  },
  openCreateTeamModal: function () {
    this.showModalComponent(CreateTeamModal, {}, 'Create New Team')
  },
  openModifyTeamModal: function (team, hideChangeName) {
    var props = {
      team: team,
      hideChangeName: hideChangeName,
    }
    this.showModalComponent(ModifyTeamModal, props, 'Manage Team')
  },

  openSplitJobModal: function (job, project, callback) {
    var props = {
      job: job,
      project: project,
      callback: callback,
    }
    var style = {width: '670px', maxWidth: '670px'}
    this.showModalComponent(SplitJobModal, props, 'Split Job', style)
  },
  openMergeModal: function (project, job, successCallback) {
    const props = {
      text:
        'See põhjustab kõigi osade ühendamise ainult ühes töös. ' +
          'Seda toimingut ei saa tühistada. ',
      successText: 'Jätka',
      successCallback: () => {
        mergeJobChunks(project, job).then(function () {
          if (successCallback) {
            successCallback.call()
          }
        })
        this.onCloseModal()
      },
      cancelText: 'Tühista',
      cancelCallback: () => {
        this.onCloseModal()
      },
    }
    this.showModalComponent(ConfirmMessageModal, props, 'Kinnitus vajalik')
  },

  openDQFModal: function () {
    var props = {
      metadata: APP.USER.STORE.metadata ? APP.USER.STORE.metadata : {},
    }
    var style = {width: '670px', maxWidth: '670px'}
    this.showModalComponent(DQFModal, props, 'DQF Preferences', style)
  },
  showDownloadWarningsModal: function (successCallback, cancelCallback) {
    ModalsActions.showModalComponent(
      ConfirmMessageModal,
      {
        cancelText: 'Lahenda probleemid',
        cancelCallback: () => cancelCallback(),
        successCallback: () => successCallback(),
        successText: 'Laadi ikkagi alla',
        text:
          'Vormingu siltide parandamata jätmine mõjutab valmisfaili allalaadimise funktsionaalsust.<br />' +
          'Teavet nende parandamise kohta saab vaadata <a style="color: #4183C4; font-weight: 700; text-decoration: underline;"' +
          ' href="https://guides.matecat.com/fixing-tags" target="_blank">kasutusjuhendist </a>. <br /><br /> ' +
          ' Kui soovid selle faili ikkagi alla laadida, siis võib osa selle sisust olla tõlkimata. Allalaaditud failis leiab need kohtad otsides märget UNTRANSLATED_CONTENT.',
      },
      'Kinnitus vajalik',
    )
  },
}

export default ModalsActions
