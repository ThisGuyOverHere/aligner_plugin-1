import React from 'react';
import SystemActions from "../../../../Actions/System.actions";
import {Link} from "react-router-dom";

const ExportModalCompleted = () => (
    <div id="completed">
        <h1>Export complete</h1>
        <h3>We’ll send you an email when the download link is ready</h3>

        <div className="btn-container">
            <button className="close-btn" tabIndex="6" type=""
                    onClick={ () => SystemActions.setExportModalStatus(false)}>
                Close
            </button>
            <button className="newalign-btn" tabIndex="6" type="">
                <Link to="/">New alignment</Link>
            </button>
        </div>
        <div className="back-to-m">
            <div className="divider"> Or </div>
            <Link to="/">Back to Matecat</Link>
        </div>
    </div>
);

export default ExportModalCompleted;
