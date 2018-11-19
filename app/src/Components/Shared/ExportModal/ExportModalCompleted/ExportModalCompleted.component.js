import React from 'react';
import SystemActions from "../../../../Actions/System.actions";
import {Link} from "react-router-dom";

const ExportModalCompleted = () => (
    <div id="completed">
        <h1>Export completed</h1>
        <h3>We will send you an e-mail when the download link is ready</h3>

        <div className="btn-container">
            <button className="close-btn" tabIndex="6" type=""
                    onClick={ () => SystemActions.setExportModalStatus(false)}>
                Close
            </button>
            <button className="newalign-btn" tabIndex="6" type="">
                <Link to="/"> New Align</Link>
            </button>
        </div>
    </div>
);

export default ExportModalCompleted;
