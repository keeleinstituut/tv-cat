import React, {useEffect, useState} from 'react'
import CommonUtils from '../../../utils/commonUtils'
import CatToolStore from '../../../stores/CatToolStore'
import CattolConstants from '../../../constants/CatToolConstants'

export const DownloadMenu = ({password, jid, isGDriveProject}) => {
  const [downloadTranslationAvailable, setDownloadTranslationAvailable] =
    useState(false)
  const [stats, setStats] = useState()
  useEffect(() => {
    if (stats) {
      const t = CommonUtils.getTranslationStatus(stats)
      const downloadable = t === 'translated' || t.indexOf('approved') > -1
      setDownloadTranslationAvailable(downloadable)
    }
  }, [stats])

  useEffect(() => {
    const updateStats = (stats) => setStats(stats)
    CatToolStore.addListener(CattolConstants.SET_PROGRESS, updateStats)
    return () => {
      CatToolStore.removeListener(CattolConstants.SET_PROGRESS, updateStats)
    }
  }, [])
  return (
    <div
      className="action-submenu ui simple pointing top center floating dropdown"
      id="action-download"
      title="Download"
    >
      <div className="dropdown-menu-overlay"></div>
      <ul
        className="menu"
        id="previewDropdown"
        data-download={
          stats && stats['TODO_FORMATTED'] == 0 && stats['ANALYSIS_COMPLETE']
            ? 'true'
            : 'false'
        }
      >
        {downloadTranslationAvailable ? (
          <li className="item downloadTranslation" data-value="translation">
            <a title="Tõlge" alt="Tõlge" href="#">
              {isGDriveProject
                ? 'Open in Google Drive'
                : 'Laadi tõlge alla'}
            </a>
          </li>
        ) : (
          <li className="item previewLink" data-value="draft">
            <a title="Mustand" alt="Mustand" href="#">
              {isGDriveProject ? 'Preview in Google Drive' : 'Mustand'}
            </a>
          </li>
        )}
        {!isGDriveProject && (
          <li className="item" data-value="original">
            <a
              className="originalDownload"
              title="Originaal"
              alt="Originaal"
              data-href={`/?action=downloadOriginal&id_job=${jid}&password=${password}&download_type=all`}
              target="_blank"
            >
              Originaal
            </a>
          </li>
        )}
        {isGDriveProject && (
          <li className="item">
            <a
              className="originalsGDrive"
              title="Original in Google Drive"
              alt="Original in Google Drive"
              href="javascript:void(0)"
            >
              Original in Google Drive
            </a>
          </li>
        )}

        <li className="item" data-value="xlif">
          <a
            className="sdlxliff"
            title="Ekspordi XLIFF"
            alt="Ekspordi XLIFF"
            data-href={`/SDLXLIFF/${jid}/${password}/${jid}.zip`}
            target="_blank"
          >
            Ekspordi XLIFF
          </a>
        </li>

        <li className="item" data-value="tmx">
          <a
            rel="noreferrer"
            className="tmx"
            title="Ekspordi TMX"
            alt="Ekspordi TMX"
            href={`/TMX/${jid}/${password}`}
            target="_blank"
          >
            Ekspordi TMX
          </a>
        </li>
      </ul>
    </div>
  )
}
