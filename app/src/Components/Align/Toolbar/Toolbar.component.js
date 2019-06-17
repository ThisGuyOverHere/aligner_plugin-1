import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ToolbarSelectionComponent from "./ToolbarSelection/ToolbarSelection.component";
import ToolbarActionsComponent from "./ToolbarActions/ToolbarActions.component";
import PropTypes from "prop-types";
import ToolbarRightHintComponent from "./ToolbarRightHint/ToolbarRightHint.component";
import SearchComponent from "./Search/Search.component";
import Hotkeys from "react-hot-keys";
import HideSegments from "./HideSegments/HideSegments.component";
import MisalignedSegments from "./MisalignedSegments/MisalignedSegments.component";

class ToolbarComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
    };

    constructor(props) {
        super(props);
        this.state = {
            hintOpened: false,
            searchStatus: false,
            hideSegmentsNavigator: false,
            misalignedSegments: false,
            selection: {
                source: {
                    count: 0,
                    list: [],
                    map: {}
                },
                target: {
                    count: 0,
                    list: [],
                    map: {}
                },
                count: 0
            },
        };
    }


    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.ADD_SEGMENT_TO_SELECTION, this.storeSelection);
    }

    render() {
        const {searchStatus, hideSegmentsNavigator, misalignedSegments} = this.state;
        const {job: {counters: {misalignmentsIndexesMap, hideIndexesMap }}} = this.props;

        return (
            <div id="toolbar">
                <div>
                    {!!this.state.selection.count && <ToolbarSelectionComponent selection={this.state.selection}/>}
                </div>
                <div>
                    <ToolbarActionsComponent selection={this.state.selection} jobConf={this.props.job.config}
                                             job={this.props.job}/>
                </div>
                <div className="toolbar-icons-container">
                    <div onClick={this.onHideSegmentsClick} className="hide-ico">
                        <span className={hideSegmentsNavigator ? "counter ico-active" : "counter"}>
                            {hideIndexesMap.length}
                        </span>
                        <svg className={hideSegmentsNavigator ? "ico-active" : ""}  id="Livello_1" data-name="Livello 1" xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24">
                            <path className="cls-1"
                                  d="M21.69,22.15H2.29a1.92,1.92,0,0,1-1.53-.72,1.83,1.83,0,0,1-.1-2.05L10.42,2.82a1.84,1.84,0,0,1,3.14,0l9.77,16.58a1.81,1.81,0,0,1-.11,2A1.91,1.91,0,0,1,21.69,22.15Zm-9.91-18L2.44,20a.24.24,0,0,0,.2.36H21.35a.24.24,0,0,0,.21-.36L12.19,4.1A.24.24,0,0,0,11.78,4.1Z"/>
                            <path className="cls-1"
                                  d="M12.38,17a2,2,0,0,1-2-1.84L9,14a4.47,4.47,0,0,0-.51.77.41.41,0,0,0,0,.4,4.42,4.42,0,0,0,3.93,2.46,4.2,4.2,0,0,0,1.08-.15L12.74,17A2,2,0,0,1,12.38,17Zm4.35.81L15.2,16.62a4.82,4.82,0,0,0,1.12-1.42.41.41,0,0,0,0-.4,4.44,4.44,0,0,0-3.94-2.45,4.23,4.23,0,0,0-2,.52L8.59,11.51a.22.22,0,0,0-.31,0h0L8,11.89a.25.25,0,0,0,0,.32l8.14,6.29a.23.23,0,0,0,.31,0h0l.28-.35A.23.23,0,0,0,16.73,17.8Zm-2.55-2-.54-.42a1.14,1.14,0,0,0,.07-.41A1.29,1.29,0,0,0,12,13.73a.61.61,0,0,1,.13.39.59.59,0,0,1,0,.13l-1-.78A2,2,0,0,1,12.38,13a2,2,0,0,1,2,2h0a1.92,1.92,0,0,1-.2.83Z"/>
                        </svg>
                    </div>
                    {hideSegmentsNavigator &&
                    <HideSegments close={this.closeHideSegmentsNavigator} job={this.props.job}/>}

                    <div onClick={this.onMisalignedSegmentsClick} className="misalignment-ico">
                        <span className={misalignedSegments ? "counter ico-active" : "counter"}>
                            {misalignmentsIndexesMap.length}
                        </span>
                        <svg className={misalignedSegments ? "ico-active" : ""} id="Livello_1" data-name="Livello 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path className="cls-1"
                                  d="M21.69,22.15H2.29a1.92,1.92,0,0,1-1.53-.72,1.83,1.83,0,0,1-.1-2.05L10.42,2.82a1.84,1.84,0,0,1,3.14,0l9.77,16.58a1.81,1.81,0,0,1-.11,2A1.91,1.91,0,0,1,21.69,22.15Zm-9.91-18L2.44,20a.24.24,0,0,0,.2.36H21.35a.24.24,0,0,0,.21-.36L12.19,4.1A.24.24,0,0,0,11.78,4.1Z"/>
                            <path className="cls-1"
                                  d="M13.63,17.1A1.64,1.64,0,1,1,12,15.46,1.65,1.65,0,0,1,13.63,17.1ZM10.54,9.51l.28,4.88a.47.47,0,0,0,.49.41h1.36a.47.47,0,0,0,.49-.41l.28-4.88A.46.46,0,0,0,13,9.06H11A.46.46,0,0,0,10.54,9.51Z"/>
                        </svg>
                    </div>
                    {misalignedSegments &&
                    <MisalignedSegments close={this.closeMisalignedSegmentsNavigator} job={this.props.job}/>}

                    <Hotkeys
                        keyName="command+f,ctrl+f,esc"
                        onKeyDown={this.handlerSearch}>
                        {searchStatus && <SearchComponent close={this.closeSearch} job={this.props.job}/>}
                    </Hotkeys>
                    <i className=" hint icon question circle outline" onClick={this.hintModalOpened}/>
                    <i className={searchStatus ? "search-ico icon search ico-active" : "search-ico icon search"} onClick={this.onSearchIconClick}/>
                </div>
                {this.state.hintOpened && <ToolbarRightHintComponent close={this.hintModalOpened}/>}
            </div>
        );
    }

    handlerSearch = (keyName, e, handle) => {
        e.preventDefault();
        this.setState({
            searchStatus: keyName !== 'esc'
        })
    };

    closeSearch = () => {
        this.setState({
            searchStatus: false
        })
    };

    closeHideSegmentsNavigator = () => {
        this.setState({
            hideSegmentsNavigator: false
        })
    };

    closeMisalignedSegmentsNavigator = () => {
        this.setState({
            misalignedSegments: false
        })
    };

    onSearchIconClick = () => {
        const {searchStatus, hideSegmentsNavigator, misalignedSegments} = this.state;
        if (!searchStatus && hideSegmentsNavigator) {
            this.setState({
                searchStatus: !searchStatus,
                hideSegmentsNavigator: false
            })
        } else if (!searchStatus && misalignedSegments) {
            this.setState({
                searchStatus: !searchStatus,
                misalignedSegments: false
            })
        }

        this.setState({
            searchStatus: !searchStatus,
        })
    };


    onHideSegmentsClick = () => {
        const {searchStatus, hideSegmentsNavigator, misalignedSegments} = this.state;
        if (!hideSegmentsNavigator && searchStatus) {
            this.setState({
                hideSegmentsNavigator: !hideSegmentsNavigator,
                searchStatus: false,
            })
        } else if (!hideSegmentsNavigator && misalignedSegments) {
            this.setState({
                hideSegmentsNavigator: !hideSegmentsNavigator,
                misalignedSegments: false,
            })
        }

        this.setState({
            hideSegmentsNavigator: !this.state.hideSegmentsNavigator
        })
    }

    onMisalignedSegmentsClick = () => {
        const {searchStatus, hideSegmentsNavigator, misalignedSegments} = this.state;
        if (!misalignedSegments && searchStatus) {
            this.setState({
                misalignedSegments: !misalignedSegments,
                searchStatus: false,
            })
        } else if (!misalignedSegments && hideSegmentsNavigator) {
            this.setState({
                misalignedSegments: !misalignedSegments,
                hideSegmentsNavigator: false,
            })
        }
        this.setState({
            misalignedSegments: !misalignedSegments
        })
    };

    storeSelection = (selection) => {
        this.setState({
            selection: selection
        })
    };

    hintModalOpened = () => {
        this.setState({
            hintOpened: !this.state.hintOpened,
        })
    };
}

export default ToolbarComponent;
