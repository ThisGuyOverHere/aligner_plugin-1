import React, {Component} from 'react';
import PropTypes from "prop-types";
import JobComponent from "./Job/Job.component";
import ProjectActions from "../../Actions/Project.actions";
import ToolbarComponent from "./Toolbar/Toolbar.component";
import {syncWithBackend} from "../../Helpers/SystemUtils.helper";
import ProjectStore from "../../Stores/Project.store";
import ProjectConstants from "../../Constants/Project.constants";
import JobError from "./JobError/JobError.component";

class AlignComponent extends Component {
    static propTypes = {
        match: PropTypes.shape({
            params: PropTypes.shape({
                password: PropTypes.any,
                jobID: PropTypes.any
            })

        })
    };
    constructor(props) {
        super(props);
        this.state = {
            job: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.jobID
                },
                rows: [],
                rowsDictionary: {
                    source: {},
                    target: {}
                }
            },
            inSync: false,
            getJobError: false
        };
        ProjectActions.setJobID(this.props.match.params.jobID, this.props.match.params.password)
    }


    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.addListener(ProjectConstants.GET_JOB_ERROR, this.getJobError);
        ProjectActions.getSegments(this.props.match.params.jobID, this.props.match.params.password);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.RENDER_ROWS, this.setRows);
        ProjectStore.removeListener(ProjectConstants.GET_JOB_ERROR, this.getJobError);
    }

    render() {
        return (
            <div id="Align">
                <ToolbarComponent jobConf={this.state.job.config}/>
                {this.state.job.rows && <JobComponent inSync={this.state.inSync} job={this.state.job}/>}
                {this.state.getJobError && <JobError/>}
            </div>
        );
    }

    getJobError = (error) => {
        this.setState({
            getJobError: error
        })
    };

    setRows = (job, syncAPI) => {
        let rows = [];
        let deletes = [];
        let matches = [];
        let previousJob = this.state.job;
        let rowsDictionary = {
            source: {},
            target: {}
        };
        job.source.map((e, index) => {
            rowsDictionary.source[e.order] = job.target[index].order;
            rowsDictionary.target[job.target[index].order] = e.order;
            //todo: send API for remove empty/empty from DB
            if (e.content_clean || job.target[index].content_clean) {
                rows.push({
                    source: e,
                    target: job.target[index]
                });
            } else {
                deletes.push(index);
                matches.push({
                    type: 'source',
                    order: e.order
                });
                matches.push({
                    type: 'target',
                    order: job.target[index].order
                });
            }
        });

        previousJob.rows = rows;
        previousJob.rowsDictionary = rowsDictionary;


        let inSync = false;
        if (syncAPI) {
            inSync = true;
            syncWithBackend(syncAPI, () => {
                if (deletes.length > 0) {
                    setTimeout(() => {
                        ProjectActions.deleteEmptyRows(deletes, matches);
                    }, 0);

                }
                this.setState({
                    inSync: false
                })
            });
        } else {
            if (deletes.length > 0) {
                setTimeout(() => {
                    ProjectActions.deleteEmptyRows(deletes, matches);
                }, 0);

            }
        }

        this.setState({
            job: previousJob,
            inSync: inSync
        })
    };

}

export default AlignComponent;
