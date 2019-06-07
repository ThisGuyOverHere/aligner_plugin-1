import React, {Component} from 'react';
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import ToolbarSelectionComponent from "./ToolbarSelection/ToolbarSelection.component";
import ToolbarActionsComponent from "./ToolbarActions/ToolbarActions.component";
import PropTypes from "prop-types";
import ToolbarRightHintComponent from "./ToolbarRightHint/ToolbarRightHint.component";
import SearchComponent from "./Search/Search.component";
import Hotkeys from "react-hot-keys";

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
        return (
            <div id="toolbar">
                <div>
                    {!!this.state.selection.count && <ToolbarSelectionComponent selection={this.state.selection}/>}
                </div>
                <div>
                    <ToolbarActionsComponent selection={this.state.selection} jobConf={this.props.job.config}/>
                </div>
                <div>
                    <Hotkeys
                        keyName="command+f,ctrl+f,esc"
                        onKeyDown={this.handlerSearch}>
                        {this.state.searchStatus && <SearchComponent close={this.closeSearch} job={this.props.job}/>}
                    </Hotkeys>
                    <i className=" hint icon question circle outline" onClick={this.hintModalOpened}/>
                    <i className={"search-ico icon search"} onClick={this.onSearchIconClick} />
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

    onSearchIconClick = () => {
        this.setState({
            searchStatus: !this.state.searchStatus
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
