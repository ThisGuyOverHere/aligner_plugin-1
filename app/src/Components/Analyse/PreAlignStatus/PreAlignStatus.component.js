import React, {Component} from 'react';
import {Redirect} from 'react-router';
import {httpAlignJob, httpGetAlignmentInfo} from "../../../HttpRequests/Alignment.http";
import env from "../../../Constants/Env.constants";
import PropTypes from "prop-types";

class PreAlignStatus extends Component {
    static propTypes = {
        jobId: PropTypes.string,
        jobPassword: PropTypes.string,
        actualPhase: PropTypes.number,
        progress: PropTypes.number
    };

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: this.props.jobPassword,
                id: this.props.jobId,
                progress: this.props.progress
            }
        }
    };

    render() {
        if (this.props.progress === 100) {
            return <Redirect to={'/job/' + this.state.job.id + '/' + this.state.job.password + '/align'}/>;
        } else {
            return <div>
                <h3>Volume analysis... </h3>
                <div className="bar-container">
                    <div className='ui progress' data-percent={this.props.progress}>
                        <div className='bar' style={{width: this.props.progress + '%'}}>
                            {/* <div className='progress'>{this.state.progress + '%'}</div> */}
                        </div>
                    </div>
                    <div className="percentage">{this.props.progress + '%'}</div>
                </div>
            </div>
        }
    }
}

export default PreAlignStatus;