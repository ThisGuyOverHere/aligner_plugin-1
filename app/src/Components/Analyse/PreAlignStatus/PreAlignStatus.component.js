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
    };

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: this.props.jobPassword,
                id: this.props.jobId
            },
            progress: 0
        }
    };

    componentDidUpdate(prevProps) {
        if (this.props.actualPhase !== prevProps.actualPhase) {
            this.setState(
                {
                    progress: (this.props.actualPhase + 1) * env.progressPercent,
                }
            );
        }
    }

    render() {
        if (this.state.progress === 100) {
            return <Redirect to={'/job/' + this.state.job.id + '/' + this.state.job.password + '/align'}/>;
        } else {
            return <div>
                <h3>Volume analysis... </h3>
                <div className="bar-container">
                    <div className='ui progress' data-percent={this.state.progress}>
                        <div className='bar' style={{width: this.state.progress + '%'}}>
                            {/* <div className='progress'>{this.state.progress + '%'}</div> */}
                        </div>
                    </div>
                    <div className="percentage">{this.state.progress + '%'}</div>
                </div>
            </div>
        }
    }
}

export default PreAlignStatus;